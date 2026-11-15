<?php
require __DIR__ . '/lib/bootstrap.php';
$active = 'careers.php';
$me = require_applicant();
$a = q1("SELECT a.*, j.title jtitle, j.company jcompany, j.slug jslug, j.status jstatus, j.closing_date jclosing, j.extra_question jq, j.extra_required jqr, j.location jloc, j.type jtype
        FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.id=? AND a.applicant_id=?", [(int)($_GET['id'] ?? $_POST['id'] ?? 0), $me['id']]);
if (!$a) { http_response_code(404); $title = 'Not found'; $desc = ''; include __DIR__ . '/inc/head.php'; echo '<section class="section"><div class="container" style="text-align:center"><h1>Application not found</h1><p><a href="portal.php">Back to my applications</a></p></div></section>'; include __DIR__ . '/inc/foot.php'; exit; }
$id = (int)$a['id'];
$canEdit = (int)$a['edit_unlocked'] === 1 && $a['status'] === 'More Details Needed';
$errors = []; $v = ['name' => $a['name'], 'phone' => $a['phone'], 'linkedin' => $a['linkedin'], 'cover_letter' => $a['cover_letter'], 'answer' => $a['answer'], 'message' => ''];
$nDocs = (int)qv("SELECT COUNT(*) FROM application_files WHERE application_id=?", [$id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) $errors['form'] = 'Your files are larger than this server allows (maximum ' . upload_limit_label() . ' in total). Please send smaller files.';
    else {
        csrf_check();
        if (!$canEdit) { flash('This application cannot be edited right now. Editing opens only when the hiring team asks for more details.', 'err'); redirect('portal-app.php?id=' . $id); }
        foreach (['name', 'phone', 'linkedin'] as $k) $v[$k] = line(post($k, 200));
        $v['cover_letter'] = post('cover_letter', 3500); $v['answer'] = post('answer', 2500); $v['message'] = post('message', 1500);
        if (mb_strlen($v['name']) < 3 || !str_contains($v['name'], ' ')) $errors['name'] = 'Please enter your full name.';
        if (!preg_match('/^\+?[0-9][0-9 \-()]{6,18}$/', $v['phone'])) $errors['phone'] = 'Enter a valid phone number.';
        $li = $v['linkedin']; if ($li !== '' && !preg_match('~^https?://~i', $li)) $li = 'https://' . $li;
        if ($li === '' || !filter_var($li, FILTER_VALIDATE_URL) || !preg_match('/(^|\.)linkedin\.com$/', strtolower((string)parse_url($li, PHP_URL_HOST)))) $errors['linkedin'] = 'Enter the link to your LinkedIn profile.';
        else $v['linkedin'] = mb_substr($li, 0, 250);
        if (mb_strlen(trim($v['cover_letter'])) < 100) $errors['cover_letter'] = 'Your cover letter should be at least 100 characters.';
        if ($a['jq'] && $a['jqr'] && mb_strlen(trim($v['answer'])) < 10) $errors['answer'] = 'Please answer this question.';
        if (mb_strlen(trim($v['message'])) < 5) $errors['message'] = 'Please add a short note about what you changed or added.';
        // new files (validated here, stored only if everything else is fine)
        $newCv = [null, null]; $newDocs = []; $docs = $_FILES['docs'] ?? null;
        $list = [];
        if ($docs && is_array($docs['name'])) foreach ($docs['name'] as $i => $n) if ($docs['error'][$i] !== UPLOAD_ERR_NO_FILE) $list[] = ['name' => $n, 'type' => $docs['type'][$i], 'tmp_name' => $docs['tmp_name'][$i], 'error' => $docs['error'][$i], 'size' => $docs['size'][$i]];
        if ($nDocs + count($list) > 8) $errors['docs'] = 'You can attach up to 8 extra documents in total.';
        if (!$errors) {
            $stored = [];
            [$s, $o, $er] = store_upload($_FILES['cv'] ?? null, CV_TYPES);
            if ($s) { $newCv = [$s, $o]; $stored[] = $s; } elseif ($er !== 'nofile') $errors['cv'] = $er;
            foreach ($list as $f) { [$s, $o, $er] = store_upload($f, DOC_TYPES); if ($s) { $newDocs[] = [$s, $o]; $stored[] = $s; } else { $errors['docs'] = $er === 'nofile' ? 'A file was empty.' : $er; break; } }
            if ($errors) { foreach ($stored as $s) delete_upload($s); }
        }
        if (!$errors) {
            try {
                $pdo = db(); $pdo->beginTransaction();
                $oldCv = $a['cv_file']; $cvFile = $newCv[0] ?: $a['cv_file']; $cvName = $newCv[0] ? $newCv[1] : $a['cv_name'];
                run("UPDATE applications SET name=?,phone=?,linkedin=?,cover_letter=?,answer=?,cv_file=?,cv_name=?,status='Under Review',edit_unlocked=0,updated_at=? WHERE id=? AND applicant_id=?",
                    [$v['name'], $v['phone'], $v['linkedin'], trim($v['cover_letter']), trim($v['answer']), $cvFile, $cvName, now(), $id, $me['id']]);
                foreach ($newDocs as [$s, $o]) run("INSERT INTO application_files(application_id,file,original,label,created_at) VALUES(?,?,?,?,?)", [$id, $s, $o, 'Added by applicant', now()]);
                $bits = [];
                if ($newCv[0]) $bits[] = 'new CV'; if ($newDocs) $bits[] = count($newDocs) . ' extra document' . (count($newDocs) > 1 ? 's' : '');
                app_event($id, 'applicant_update', 'You updated your application' . ($bits ? ' (' . implode(', ', $bits) . ')' : '') . ': ' . trim($v['message']), $v['name']);
                $pdo->commit();
                if ($newCv[0]) delete_upload($oldCv);
                $app = q1("SELECT * FROM applications WHERE id=?", [$id]);
                send_mail(careers_inbox(), 'Applicant updated application ' . $a['code'] . ' - ' . $a['jtitle'], "{$v['name']} responded to your request for more details.\n\nMessage: " . trim($v['message']) . "\n\nReview: " . site_url('admin/application.php?id=' . $id) . "\n", $a['email']);
                flash('Thank you. Your update was sent and your application is back under review.');
                redirect('portal-app.php?id=' . $id);
            } catch (Throwable $ex) {
                if (db()->inTransaction()) db()->rollBack();
                foreach (array_merge([$newCv[0]], array_column($newDocs, 0)) as $s) delete_upload($s);
                $errors['form'] = 'Sorry, something went wrong saving your update. Please try again.';
            }
        }
    }
}
$events = q("SELECT * FROM application_events WHERE application_id=? AND visible=1 ORDER BY id DESC", [$id]);
$files = q("SELECT * FROM application_files WHERE application_id=? ORDER BY id", [$id]);
[$hl, $msg, $tone] = app_status_info($a['status']); $stage = app_stage($a['status']);
$jstate = job_state(['status' => $a['jstatus'], 'closing_date' => $a['jclosing']]);
$title = $a['code'] . ' | My application'; $desc = 'Track your application.';
include __DIR__ . '/inc/head.php';
$err = fn($k) => isset($errors[$k]) ? '<span class="req-err">' . e($errors[$k]) . '</span>' : '';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><a href="portal.php">My applications</a><span>/</span><span class="cur"><?= e($a['code']) ?></span></div>
  <h1><?= e($a['jtitle']) ?></h1><p><?= e($a['jcompany']) ?> · Reference <b style="color:#fff"><?= e($a['code']) ?></b> · Applied <?= e(dt($a['created_at'])) ?></p>
</div></section>
<section class="section"><div class="container portal-wrap">
  <?php foreach ($_SESSION['flash'] ?? [] as [$t, $m]): ?><div class="alert <?= $t === 'ok' ? 'ok' : 'fail' ?>"><?= e($m) ?></div><?php endforeach; unset($_SESSION['flash']); ?>
  <?php if (isset($errors['form'])): ?><div class="alert fail"><?= e($errors['form']) ?></div><?php endif; ?>

  <div class="tracker">
    <?php foreach (['Submitted', 'In review', 'Decision'] as $i => $lbl): ?>
      <div class="trk <?= $i < $stage ? 'done' : ($i === $stage ? 'now' : '') ?> <?= $i === $stage && $a['status'] === 'More Details Needed' ? 'warn' : '' ?> <?= $i === 2 && $stage === 2 ? 'tone-' . e($tone) : '' ?>"><span><?= $i < $stage ? svg_icon('check') : $i + 1 ?></span><b><?= $lbl ?></b></div>
    <?php endforeach; ?>
  </div>

  <div class="status-panel tone-<?= e($tone) ?>">
    <div><span class="st st-<?= e(strtolower(str_replace(' ', '-', $a['status']))) ?>"><?= e($a['status']) ?></span><h2><?= e($hl) ?></h2><p><?= e($msg) ?></p>
    <?php if ($a['status'] === 'More Details Needed' && trim((string)$a['info_request'])): ?><blockquote><b>From the hiring team:</b><br><?= nl2br(e($a['info_request'])) ?></blockquote><?php endif; ?>
    <?php if (in_array($a['status'], ['Accepted', 'Rejected', 'Not Qualified'], true) && trim((string)$a['decision_note'])): ?><blockquote><b>Note from the hiring team:</b><br><?= nl2br(e($a['decision_note'])) ?></blockquote><?php endif; ?></div>
    <div class="sp-job"><small>Job status</small><span class="st st-<?= e(strtolower($jstate)) ?>"><?= e($jstate) ?></span><?php if ($a['jclosing']): ?><small>Closing date: <?= e(dt($a['jclosing'])) ?></small><?php endif; ?><a href="job.php?j=<?= e($a['jslug']) ?>">View the role</a></div>
  </div>

  <div class="portal-grid">
    <div>
    <?php if ($canEdit): ?>
      <div class="form-card" id="update">
        <h3>Update your application</h3><p>The hiring team opened your application for changes. Update what is needed, add documents and tell us what you changed. Once you send it, editing closes again.</p>
        <form method="post" enctype="multipart/form-data" action="portal-app.php?id=<?= $id ?>" novalidate><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
          <div class="field<?= isset($errors['name']) ? ' bad' : '' ?>"><label for="name">Full name</label><input id="name" name="name" value="<?= e($v['name']) ?>" required><?= $err('name') ?></div>
          <div class="form-row"><div class="field"><label>Email (cannot be changed)</label><input value="<?= e($a['email']) ?>" readonly></div>
            <div class="field<?= isset($errors['phone']) ? ' bad' : '' ?>"><label for="phone">Phone number</label><input id="phone" name="phone" value="<?= e($v['phone']) ?>" required><?= $err('phone') ?></div></div>
          <div class="field<?= isset($errors['linkedin']) ? ' bad' : '' ?>"><label for="linkedin">LinkedIn profile</label><input id="linkedin" name="linkedin" value="<?= e($v['linkedin']) ?>" required><?= $err('linkedin') ?></div>
          <div class="field<?= isset($errors['cv']) ? ' bad' : '' ?>"><label for="cv">Replace your CV (optional)</label>
            <label class="dropfile" for="cv"><?= svg_icon('upload') ?><span><b id="cv-name">Current: <?= e($a['cv_name']) ?></b><small>Choose a new file only if you want to replace it · PDF, DOC, DOCX · up to <?= e(upload_limit_label()) ?></small></span></label>
            <input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx" class="sr-file"><?= $err('cv') ?></div>
          <div class="field<?= isset($errors['docs']) ? ' bad' : '' ?>"><label for="docs">Add documents (optional)</label>
            <label class="dropfile" for="docs"><?= svg_icon('upload') ?><span><b id="docs-name">Choose files</b><small>Certificates, portfolio, samples · PDF, DOC, DOCX, PNG, JPG · up to <?= e(upload_limit_label()) ?> each</small></span></label>
            <input id="docs" name="docs[]" type="file" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="sr-file"><?= $err('docs') ?></div>
          <div class="field<?= isset($errors['cover_letter']) ? ' bad' : '' ?>"><label for="cover_letter">Cover letter</label><textarea id="cover_letter" name="cover_letter" rows="7" maxlength="3500" required><?= e($v['cover_letter']) ?></textarea><?= $err('cover_letter') ?></div>
          <?php if (trim((string)$a['jq'])): ?><div class="field<?= isset($errors['answer']) ? ' bad' : '' ?>"><label for="answer"><?= e($a['jq']) ?></label><textarea id="answer" name="answer" rows="4" maxlength="2500"><?= e($v['answer']) ?></textarea><?= $err('answer') ?></div><?php endif; ?>
          <div class="field<?= isset($errors['message']) ? ' bad' : '' ?>"><label for="message">Message to the hiring team *</label><textarea id="message" name="message" rows="3" maxlength="1500" placeholder="Briefly say what you changed or added." required><?= e($v['message']) ?></textarea><?= $err('message') ?></div>
          <button class="btn btn-primary" type="submit">Send update <?= svg_icon('arrow') ?></button>
        </form>
      </div>
    <?php endif; ?>
      <div class="detail-card"><h3>Your application</h3>
        <dl class="kv2"><div><dt>Name</dt><dd><?= e($a['name']) ?></dd></div><div><dt>Email</dt><dd><?= e($a['email']) ?></dd></div><div><dt>Phone</dt><dd><?= e($a['phone']) ?></dd></div>
          <div><dt>LinkedIn</dt><dd><a href="<?= e($a['linkedin']) ?>" target="_blank" rel="noopener nofollow"><?= e(preg_replace('~^https?://(www\.)?~i', '', (string)$a['linkedin'])) ?></a></dd></div>
          <div><dt>CV</dt><dd><a class="filelink" href="portal-file.php?a=<?= $id ?>&amp;f=cv"><?= svg_icon('file') ?><?= e($a['cv_name']) ?></a></dd></div>
          <?php foreach ($files as $f): ?><div><dt>Document</dt><dd><a class="filelink" href="portal-file.php?a=<?= $id ?>&amp;f=<?= (int)$f['id'] ?>"><?= svg_icon('file') ?><?= e($f['original']) ?></a></dd></div><?php endforeach; ?></dl>
        <h4>Cover letter</h4><div class="msgbox"><?= nl2br(e($a['cover_letter'])) ?></div>
        <?php if (trim((string)$a['jq'])): ?><h4><?= e($a['jq']) ?></h4><div class="msgbox"><?= nl2br(e($a['answer'] ?: '—')) ?></div><?php endif; ?>
        <?php if (!$canEdit): ?><p class="muted small" style="margin-top:16px"><?= svg_icon('lock') ?> Your application is locked. If the hiring team needs anything more, they will open it for editing and tell you here.</p><?php endif; ?>
      </div>
    </div>
    <aside class="side-card"><h4>Progress</h4>
      <ul class="timeline"><?php foreach ($events as $ev): ?><li><span class="dot"></span><div><b><?= e($ev['text']) ?></b><small><?= e(dt($ev['created_at'], 'd M Y, H:i')) ?><?= $ev['actor'] ? ' · ' . e($ev['actor']) : '' ?></small></div></li><?php endforeach; ?></ul>
      <a class="btn btn-dark btn-sm" href="portal.php">All my applications</a></aside>
  </div>
</div></section>
<script>
document.querySelectorAll('.sr-file').forEach(function(i){i.addEventListener('change',function(){var n=document.getElementById(i.id+'-name');if(n&&i.files.length)n.textContent=i.files.length>1?i.files.length+' files selected':i.files[0].name})});
</script>
<?php include __DIR__ . '/inc/foot.php'; ?>
