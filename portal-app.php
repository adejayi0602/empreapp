<?php
require __DIR__ . '/lib/bootstrap.php';
$active = 'careers.php';
$me = require_applicant();
$a = q1("SELECT a.*, j.title jtitle, j.company jcompany, j.slug jslug, j.status jstatus, j.closing_date jclosing, j.extra_question jq, j.extra_required jqr, j.location jloc, j.type jtype
        FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.id=? AND a.applicant_id=?", [(int)($_GET['id'] ?? $_POST['id'] ?? 0), $me['id']]);
if (!$a) { http_response_code(404); $title = 'Not found'; $desc = ''; include __DIR__ . '/inc/head.php'; echo '<section class="section"><div class="container" style="text-align:center"><h1>Application not found</h1><p><a href="portal.php">Back to my applications</a></p></div></section>'; include __DIR__ . '/inc/foot.php'; exit; }
$id = (int)$a['id'];
$canEdit = (int)$a['edit_unlocked'] === 1 && $a['status'] === 'More Details Needed';
$errors = []; $v = ['name' => $a['name'], 'phone' => $a['phone'], 'linkedin' => $a['linkedin'], 'portfolio' => $a['portfolio'] ?? '', 'location' => $a['location'] ?? '', 'expected_pay' => $a['expected_pay'] ?? '', 'available_from' => $a['available_from'] ?? '', 'cover_letter' => $a['cover_letter'], 'answer' => $a['answer'], 'message' => ''];
$onb = q1("SELECT * FROM onboardings WHERE application_id=?", [$id]);
$nDocs = (int)qv("SELECT COUNT(*) FROM application_files WHERE application_id=?", [$id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) $errors['form'] = 'Your files are larger than this server allows (maximum ' . upload_limit_label() . ' in total). Please send smaller files.';
    else {
        csrf_check();
        if (post('action') === 'onb_task') {   // applicant completes one of their onboarding tasks
            $t = q1("SELECT t.* FROM onboarding_tasks t JOIN onboardings o ON o.id=t.onboarding_id WHERE t.id=? AND o.application_id=? AND t.owner='Employee'", [(int)post('task'), $id]);
            if (!$t || (int)$t['done']) { flash('That task is not available.', 'err'); redirect('portal-app.php?id=' . $id . '#onboarding'); }
            $fid = null;
            if ((int)$t['needs_file']) {
                [$s1, $o1, $er1] = store_upload($_FILES['file'] ?? null, DOC_TYPES);
                if (!$s1) { flash($er1 === 'nofile' ? 'Please choose the file to upload for this task.' : $er1, 'err'); redirect('portal-app.php?id=' . $id . '#onboarding'); }
                $fid = run("INSERT INTO application_files(application_id,file,original,label,created_at) VALUES(?,?,?,?,?)", [$id, $s1, $o1, $t['title'], now()]);
            }
            run("UPDATE onboarding_tasks SET done=1, done_at=?, done_by=?, file_id=? WHERE id=?", [now(), $me['name'], $fid, $t['id']]);
            app_event($id, 'onboarding', 'Onboarding task completed: ' . $t['title'], $me['name']);
            flash('Thank you. “' . $t['title'] . '” is marked as done.'); redirect('portal-app.php?id=' . $id . '#onboarding');
        }
        if (!$canEdit) { flash('This application cannot be edited right now. Editing opens only when the hiring team asks for more details.', 'err'); redirect('portal-app.php?id=' . $id); }
        foreach (['name', 'phone', 'linkedin', 'portfolio', 'location', 'expected_pay', 'available_from'] as $k) $v[$k] = line(post($k, 250));
        $v['cover_letter'] = post('cover_letter', 3500); $v['answer'] = post('answer', 2500); $v['message'] = post('message', 1500);
        if (mb_strlen($v['name']) < 3 || !str_contains($v['name'], ' ')) $errors['name'] = 'Please enter your full name.';
        $errors += hr_validate_fields($v);
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
                run("UPDATE applications SET name=?,phone=?,linkedin=?,portfolio=?,location=?,expected_pay=?,available_from=?,cover_letter=?,answer=?,cv_file=?,cv_name=?,status='Under Review',edit_unlocked=0,updated_at=? WHERE id=? AND applicant_id=?",
                    [$v['name'], $v['phone'], $v['linkedin'], $v['portfolio'], $v['location'], $v['expected_pay'], $v['available_from'], trim($v['cover_letter']), trim($v['answer']), $cvFile, $cvName, now(), $id, $me['id']]);
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
    <?php if (in_array($a['status'], ['Accepted', 'Hired', 'Rejected', 'Not Qualified'], true) && trim((string)$a['decision_note'])): ?><blockquote><b>Note from the hiring team:</b><br><?= nl2br(e($a['decision_note'])) ?></blockquote><?php endif; ?></div>
    <div class="sp-job"><small>Job status</small><span class="st st-<?= e(strtolower($jstate)) ?>"><?= e($jstate) ?></span><?php if ($a['jclosing']): ?><small>Closing date: <?= e(dt($a['jclosing'])) ?></small><?php endif; ?><a href="job.php?j=<?= e($a['jslug']) ?>">View the role</a></div>
  </div>

  <div class="portal-grid">
    <div>
    <?php if ($canEdit): ?>
      <div class="form-card" id="update">
        <h3>Update your application</h3><p>The hiring team opened your application for changes. Update what is needed, add documents and tell us what you changed. Once you send it, editing closes again.</p>
        <form method="post" enctype="multipart/form-data" action="portal-app.php?id=<?= $id ?>" novalidate><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
          <div class="field<?= isset($errors['name']) ? ' bad' : '' ?>"><label for="name">Full name *</label><input id="name" name="name" value="<?= e($v['name']) ?>" required><?= $err('name') ?></div>
          <div class="form-row"><div class="field"><label>Email (cannot be changed)</label><input value="<?= e($a['email']) ?>" readonly></div>
            <?php if (field_shown('phone')): ?><div class="field<?= isset($errors['phone']) ? ' bad' : '' ?>"><label for="phone"><?= e(field_label('phone')) ?></label><input id="phone" name="phone" value="<?= e($v['phone']) ?>" <?= field_req('phone') ? 'required' : '' ?>><?= $err('phone') ?></div><?php endif; ?></div>
          <?php if (field_shown('linkedin')): ?><div class="field<?= isset($errors['linkedin']) ? ' bad' : '' ?>"><label for="linkedin"><?= e(field_label('linkedin')) ?></label><input id="linkedin" name="linkedin" value="<?= e($v['linkedin']) ?>" <?= field_req('linkedin') ? 'required' : '' ?>><?= $err('linkedin') ?></div><?php endif; ?>
          <?php foreach (['portfolio' => 'url', 'location' => 'text', 'expected_pay' => 'text', 'available_from' => 'date'] as $xk => $xt): if (field_shown($xk)): ?>
          <div class="field<?= isset($errors[$xk]) ? ' bad' : '' ?>"><label for="<?= $xk ?>"><?= e(field_label($xk)) ?></label><input id="<?= $xk ?>" name="<?= $xk ?>" type="<?= $xt === 'url' ? 'text' : $xt ?>" value="<?= e($v[$xk]) ?>" <?= field_req($xk) ? 'required' : '' ?>><?= $err($xk) ?></div><?php endif; endforeach; ?>
          <?php if (field_shown('cv')): ?><div class="field<?= isset($errors['cv']) ? ' bad' : '' ?>"><label for="cv">Replace your CV (optional)</label>
            <label class="dropfile" for="cv"><?= svg_icon('upload') ?><span><b id="cv-name"><?= $a['cv_name'] ? 'Current: ' . e($a['cv_name']) : 'Choose a file' ?></b><small>Choose a new file only if you want to replace it · PDF, DOC, DOCX · up to <?= e(upload_limit_label()) ?></small></span></label>
            <input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx" class="sr-file"><?= $err('cv') ?></div><?php endif; ?>
          <div class="field<?= isset($errors['docs']) ? ' bad' : '' ?>"><label for="docs">Add documents (optional)</label>
            <label class="dropfile" for="docs"><?= svg_icon('upload') ?><span><b id="docs-name">Choose files</b><small>Certificates, portfolio, samples · PDF, DOC, DOCX, PNG, JPG · up to <?= e(upload_limit_label()) ?> each</small></span></label>
            <input id="docs" name="docs[]" type="file" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="sr-file"><?= $err('docs') ?></div>
          <div class="field<?= isset($errors['cover_letter']) ? ' bad' : '' ?>"><label for="cover_letter"><?= e(field_label('cover_letter', 'Cover letter')) ?></label><textarea id="cover_letter" name="cover_letter" rows="7" maxlength="3500" <?= field_req('cover_letter') ? 'required' : '' ?>><?= e($v['cover_letter']) ?></textarea><?= $err('cover_letter') ?></div>
          <?php if (trim((string)$a['jq'])): ?><div class="field<?= isset($errors['answer']) ? ' bad' : '' ?>"><label for="answer"><?= e($a['jq']) ?></label><textarea id="answer" name="answer" rows="4" maxlength="2500"><?= e($v['answer']) ?></textarea><?= $err('answer') ?></div><?php endif; ?>
          <div class="field<?= isset($errors['message']) ? ' bad' : '' ?>"><label for="message">Message to the hiring team *</label><textarea id="message" name="message" rows="3" maxlength="1500" placeholder="Briefly say what you changed or added." required><?= e($v['message']) ?></textarea><?= $err('message') ?></div>
          <button class="btn btn-primary" type="submit">Send update <?= svg_icon('arrow') ?></button>
        </form>
      </div>
    <?php endif; ?>
<?php if ($a['interview_at'] && !in_array($a['status'], ['Rejected', 'Not Qualified'], true)): ?>
      <div class="detail-card hr-card"><h3><?= svg_icon('calendar') ?> Your interview</h3>
        <p class="hr-when"><?= e(date('l, j F Y', strtotime($a['interview_at']))) ?><b><?= e(date('g:i A', strtotime($a['interview_at']))) ?></b></p>
        <?php if (trim((string)$a['interview_info'])): ?><div class="msgbox"><?= nl2br(e($a['interview_info'])) ?></div><?php endif; ?>
        <p class="muted small" style="margin-top:10px">Cannot make it? Reply to the invitation email and we will find another time.</p></div>
<?php endif; ?>
<?php if ($onb): $tasks = q("SELECT * FROM onboarding_tasks WHERE onboarding_id=? ORDER BY sort,id", [$onb['id']]); [$od, $ot] = hr_onb_progress((int)$onb['id']); ?>
      <div class="detail-card hr-card" id="onboarding"><h3><?= svg_icon('check') ?> Your onboarding</h3>
        <p class="muted" style="margin-top:-6px">Welcome aboard! <?= $onb['start_date'] ? 'Your start date is <b>' . e(dt($onb['start_date'], 'l, j F Y')) . '</b>. ' : '' ?>Complete the tasks marked <b>You</b>; our team handles the rest.</p>
        <?= hr_progress_bar($od, $ot) ?>
        <?php foreach (ONB_CATEGORIES as $cat): $ct = array_filter($tasks, fn($t) => $t['category'] === $cat); if (!$ct) continue; ?>
          <h4 class="onb-cat"><?= e($cat) ?></h4>
          <?php foreach ($ct as $t): $mine = $t['owner'] === 'Employee'; ?>
          <div class="onb-task <?= (int)$t['done'] ? 'done' : '' ?>">
            <span class="tick"><?= (int)$t['done'] ? svg_icon('check') : '' ?></span>
            <div class="ot-main"><b><?= e($t['title']) ?></b><?php if ($t['description']): ?><small><?= e($t['description']) ?></small><?php endif; ?>
              <small class="ot-meta"><?= $mine ? 'You' : 'Empre team' ?><?= $t['due_date'] ? ' · due ' . e(dt($t['due_date'])) : '' ?><?= (int)$t['done'] ? ' · done ' . e(dt($t['done_at'])) : '' ?></small>
              <?php if ($mine && !(int)$t['done']): ?>
              <form method="post" enctype="multipart/form-data" action="portal-app.php?id=<?= $id ?>" class="ot-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="onb_task"><input type="hidden" name="task" value="<?= (int)$t['id'] ?>">
                <?php if ((int)$t['needs_file']): ?><input type="file" name="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required><?php endif; ?>
                <button class="btn btn-primary btn-sm" type="submit"><?= (int)$t['needs_file'] ? 'Upload and mark done' : 'Mark as done' ?></button></form>
              <?php endif; ?></div></div>
          <?php endforeach; endforeach; ?>
      </div>
<?php endif; ?>
      <div class="detail-card"><h3>Your application</h3>
        <dl class="kv2"><div><dt>Name</dt><dd><?= e($a['name']) ?></dd></div><div><dt>Email</dt><dd><?= e($a['email']) ?></dd></div><?php if ($a['phone']): ?><div><dt>Phone</dt><dd><?= e($a['phone']) ?></dd></div><?php endif; ?>
          <?php if ($a['linkedin']): ?><div><dt>LinkedIn</dt><dd><a href="<?= e($a['linkedin']) ?>" target="_blank" rel="noopener nofollow"><?= e(preg_replace('~^https?://(www\.)?~i', '', (string)$a['linkedin'])) ?></a></dd></div><?php endif; ?>
          <?php if ($a['portfolio']): ?><div><dt>Portfolio</dt><dd><a href="<?= e($a['portfolio']) ?>" target="_blank" rel="noopener nofollow"><?= e(preg_replace('~^https?://(www\.)?~i', '', (string)$a['portfolio'])) ?></a></dd></div><?php endif; ?>
          <?php foreach (['location' => 'Location', 'expected_pay' => 'Expected pay'] as $xk => $xl): if (trim((string)$a[$xk])): ?><div><dt><?= $xl ?></dt><dd><?= e($a[$xk]) ?></dd></div><?php endif; endforeach; ?>
          <?php if ($a['available_from']): ?><div><dt>Available from</dt><dd><?= e(dt($a['available_from'])) ?></dd></div><?php endif; ?>
          <?php if ($a['cv_file']): ?><div><dt>CV</dt><dd><a class="filelink" href="portal-file.php?a=<?= $id ?>&amp;f=cv"><?= svg_icon('file') ?><?= e($a['cv_name']) ?></a></dd></div><?php endif; ?>
          <?php foreach ($files as $f): ?><div><dt>Document</dt><dd><a class="filelink" href="portal-file.php?a=<?= $id ?>&amp;f=<?= (int)$f['id'] ?>"><?= svg_icon('file') ?><?= e($f['original']) ?></a></dd></div><?php endforeach; ?></dl>
        <?php if (trim((string)$a['cover_letter'])): ?><h4>Cover letter</h4><div class="msgbox"><?= nl2br(e($a['cover_letter'])) ?></div><?php endif; ?>
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
