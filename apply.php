<?php
require __DIR__ . '/lib/bootstrap.php';
$active = 'careers.php';
$j = job_by_slug((string)($_GET['j'] ?? ''));
if (!$j) redirect('careers.php');
$me = applicant_user();
if ($me && ($ex = q1("SELECT id FROM applications WHERE job_id=? AND applicant_id=?", [$j['id'], $me['id']]))) { flash('You have already applied for this role. Here is your application.'); redirect('portal-app.php?id=' . $ex['id']); }
$open = job_is_open($j);
$errors = []; $v = ['name' => $me['name'] ?? '', 'email' => $me['email'] ?? '', 'phone' => $me['phone'] ?? '', 'linkedin' => '', 'cover_letter' => '', 'answer' => '', 'password' => '', 'password2' => ''];

if ($open && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) { $errors['form'] = 'Your file is larger than this server allows (maximum ' . upload_limit_label() . '). Please choose a smaller file.'; }
    else {
        csrf_check();
        if (post('company_site') !== '') redirect('careers.php');
        foreach (['name', 'phone', 'linkedin'] as $k) $v[$k] = line(post($k, 200));
        if (!$me) $v['email'] = line(post('email', 150));
        $v['cover_letter'] = post('cover_letter', 3500); $v['answer'] = post('answer', 2500);
        $v['password'] = (string)($_POST['password'] ?? ''); $v['password2'] = (string)($_POST['password2'] ?? '');
        if (throttled('apply:' . client_ip(), 6, 3600)) $errors['form'] = 'Too many applications from this connection. Please try again later.';
        if (mb_strlen($v['name']) < 3 || !str_contains($v['name'], ' ')) $errors['name'] = 'Please enter your full name (first and last name).';
        if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if (!preg_match('/^\+?[0-9][0-9 \-()]{6,18}$/', $v['phone'])) $errors['phone'] = 'Enter a valid phone number.';
        $li = $v['linkedin']; if ($li !== '' && !preg_match('~^https?://~i', $li)) $li = 'https://' . $li;
        $host = strtolower((string)parse_url($li, PHP_URL_HOST));
        if ($li === '' || !filter_var($li, FILTER_VALIDATE_URL) || !preg_match('/(^|\.)linkedin\.com$/', $host)) $errors['linkedin'] = 'Enter the link to your LinkedIn profile (linkedin.com/in/your-name).';
        else $v['linkedin'] = mb_substr($li, 0, 250);
        if (mb_strlen(trim($v['cover_letter'])) < 100) $errors['cover_letter'] = 'Please tell us why you are the best fit (at least 100 characters).';
        if ($j['extra_question'] && $j['extra_required'] && mb_strlen(trim($v['answer'])) < 10) $errors['answer'] = 'Please answer this question (at least a short sentence).';
        if (!$me) {
            if (!isset($errors['email']) && q1("SELECT id FROM applicants WHERE email=?", [$v['email']]))
                $errors['email'] = 'An account with this email already exists. <a href="portal-login.php?next=' . urlencode('apply.php?j=' . $j['slug']) . '">Sign in</a> to apply with it.';
            if (strlen($v['password']) < 8) $errors['password'] = 'Choose a password of at least 8 characters.';
            elseif ($v['password'] !== $v['password2']) $errors['password2'] = 'The passwords do not match.';
        }
        if (empty($_POST['consent'])) $errors['consent'] = 'Please confirm to continue.';
        [$cvStored, $cvName, $cvErr] = store_upload($_FILES['cv'] ?? null, $errors ? [] : CV_TYPES);   // only stored when everything else is valid
        if ($errors) { if ($cvStored) { delete_upload($cvStored); } $cvStored = null; }
        elseif ($cvErr === 'nofile') $errors['cv'] = 'Please upload your CV (PDF or Word).';
        elseif ($cvErr) $errors['cv'] = $cvErr;
        elseif (!$errors) {
            try {
                $pdo = db(); $pdo->beginTransaction();
                $aid = $me['id'] ?? 0;
                if (!$aid) $aid = run("INSERT INTO applicants(name,email,phone,password_hash,created_at) VALUES(?,?,?,?,?)", [$v['name'], $v['email'], $v['phone'], password_hash($v['password'], PASSWORD_DEFAULT), now()]);
                $id = 0;
                $code = insert_with_code('APP', 'applications', 'code', function ($c) use (&$id, $j, $aid, $v, $cvStored, $cvName) {
                    $id = run("INSERT INTO applications(code,job_id,applicant_id,name,email,phone,linkedin,cv_file,cv_name,cover_letter,answer,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        [$c, $j['id'], $aid, $v['name'], $v['email'], $v['phone'], $v['linkedin'], $cvStored, $cvName, trim($v['cover_letter']), trim($v['answer']), 'Received', now(), now()]);
                });
                app_event($id, 'submitted', 'Application submitted for ' . $j['title'] . '.', $v['name']);
                $pdo->commit();
                throttle_hit('apply:' . client_ip());
                $app = q1("SELECT * FROM applications WHERE id=?", [$id]);
                mail_new_application($app, $j);
                applicant_login((int)$aid);
                flash('Application submitted. Your reference is ' . $code . '. You can follow its progress here.');
                redirect('portal-app.php?id=' . $id . '&new=1');
            } catch (Throwable $ex) {
                if (db()->inTransaction()) db()->rollBack();
                delete_upload($cvStored);
                $errors['form'] = strpos($ex->getMessage(), 'UNIQUE') !== false ? 'You have already applied for this role with this email.' : 'Sorry, something went wrong saving your application. Please try again.';
            }
        }
    }
}
$title = 'Apply: ' . $j['title'] . ' | Empre Technologies Careers'; $desc = 'Apply for ' . $j['title'] . ' at ' . $j['company'] . '.';
include __DIR__ . '/inc/head.php';
$err = fn($k) => isset($errors[$k]) ? '<span class="req-err">' . ($k === 'email' ? $errors[$k] : e($errors[$k])) . '</span>' : '';
$lim = upload_limit_label();
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><a href="careers.php">Careers</a><span>/</span><a href="job.php?j=<?= e($j['slug']) ?>"><?= e($j['title']) ?></a><span>/</span><span class="cur">Apply</span></div>
  <h1>Apply: <?= e($j['title']) ?></h1><p><?= e($j['company']) ?><?= $j['type'] ? ' · ' . e($j['type']) : '' ?><?= $j['location'] ? ' · ' . e($j['location']) : '' ?></p>
</div></section>
<section class="section"><div class="container apply-wrap">
<?php if (!$open): ?>
  <div class="form-card" style="text-align:center"><h3>Applications are closed</h3><p>This role is no longer accepting applications.</p><a class="btn btn-primary" href="careers.php">See open roles <?= svg_icon('arrow') ?></a></div>
<?php else: ?>
  <div class="form-card">
    <h3>Application form</h3><p>Fields marked * are required. After you apply you get a private portal to track progress.</p>
    <?php if (isset($errors['form'])): ?><div class="alert fail"><?= e($errors['form']) ?></div><?php endif; ?>
    <?php if ($errors && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($errors['form'])): ?><div class="alert fail">Please fix the highlighted fields. Your CV file must be selected again after an error.</div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="apply.php?j=<?= e($j['slug']) ?>" novalidate>
      <?= csrf_field() ?>
      <h4 class="form-sec">About you</h4>
      <div class="field<?= isset($errors['name']) ? ' bad' : '' ?>"><label for="name">Full name *</label><input id="name" name="name" value="<?= e($v['name']) ?>" autocomplete="name" required><?= $err('name') ?></div>
      <div class="form-row">
        <div class="field<?= isset($errors['email']) ? ' bad' : '' ?>"><label for="email">Email address *</label><input id="email" name="email" type="email" value="<?= e($v['email']) ?>" autocomplete="email" <?= $me ? 'readonly' : '' ?> required><?= $err('email') ?></div>
        <div class="field<?= isset($errors['phone']) ? ' bad' : '' ?>"><label for="phone">Phone number *</label><input id="phone" name="phone" type="tel" value="<?= e($v['phone']) ?>" autocomplete="tel" placeholder="+234…" required><?= $err('phone') ?></div>
      </div>
      <div class="field<?= isset($errors['linkedin']) ? ' bad' : '' ?>"><label for="linkedin">LinkedIn profile *</label><input id="linkedin" name="linkedin" value="<?= e($v['linkedin']) ?>" placeholder="https://www.linkedin.com/in/your-name" inputmode="url" required><?= $err('linkedin') ?></div>
      <h4 class="form-sec">Your application</h4>
      <div class="field<?= isset($errors['cv']) ? ' bad' : '' ?>"><label for="cv">Upload your CV *</label>
        <label class="dropfile" for="cv"><?= svg_icon('upload') ?><span><b id="cv-name">Choose a file</b><small>PDF, DOC or DOCX · up to <?= e($lim) ?></small></span></label>
        <input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx" class="sr-file" required><?= $err('cv') ?></div>
      <div class="field<?= isset($errors['cover_letter']) ? ' bad' : '' ?>"><label for="cover_letter">Cover letter: why are you the best fit for this role? *</label>
        <textarea id="cover_letter" name="cover_letter" rows="7" maxlength="3500" placeholder="Tell us about your experience, what you would bring to the role and why you want to join us." required><?= e($v['cover_letter']) ?></textarea>
        <span class="count" data-for="cover_letter">0 / 3500</span><?= $err('cover_letter') ?></div>
      <?php if (trim((string)$j['extra_question'])): ?>
      <div class="field<?= isset($errors['answer']) ? ' bad' : '' ?>"><label for="answer"><?= e($j['extra_question']) ?><?= $j['extra_required'] ? ' *' : ' (optional)' ?></label>
        <textarea id="answer" name="answer" rows="4" maxlength="2500" <?= $j['extra_required'] ? 'required' : '' ?>><?= e($v['answer']) ?></textarea><?= $err('answer') ?></div>
      <?php endif; ?>
      <?php if (!$me): ?>
      <h4 class="form-sec">Create your applicant portal login</h4>
      <p class="muted small" style="margin:-6px 0 14px">Use your email and this password to follow your application and upload anything we ask for.</p>
      <div class="form-row">
        <div class="field<?= isset($errors['password']) ? ' bad' : '' ?>"><label for="password">Password *</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><?= $err('password') ?></div>
        <div class="field<?= isset($errors['password2']) ? ' bad' : '' ?>"><label for="password2">Confirm password *</label><input id="password2" name="password2" type="password" minlength="8" autocomplete="new-password" required><?= $err('password2') ?></div>
      </div>
      <?php else: ?><p class="muted small">You are signed in as <b><?= e($me['email']) ?></b>. This application will be added to your portal.</p><?php endif; ?>
      <div class="field<?= isset($errors['consent']) ? ' bad' : '' ?>"><label class="check"><input type="checkbox" name="consent" value="1" <?= !empty($_POST['consent']) ? 'checked' : '' ?>> <span>I confirm the information above is accurate and I agree that Empre Technologies may store and process it for recruitment.</span></label><?= $err('consent') ?></div>
      <div class="hp" aria-hidden="true"><label>Leave empty <input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
      <button class="btn btn-primary" type="submit">Submit application <?= svg_icon('arrow') ?></button>
    </form>
  </div>
  <aside class="apply-side">
    <div class="side-card"><h4><?= e($j['title']) ?></h4><ul>
      <li><?= svg_icon('briefcase') ?><?= e($j['company']) ?></li><?php if ($j['type']): ?><li><?= svg_icon('clock') ?><?= e($j['type']) ?></li><?php endif; ?>
      <?php if ($j['location']): ?><li><?= svg_icon('pin') ?><?= e($j['location']) ?></li><?php endif; ?>
      <?php if ($j['closing_date']): ?><li><?= svg_icon('calendar') ?>Apply by <?= e(dt($j['closing_date'])) ?></li><?php endif; ?></ul>
      <a href="job.php?j=<?= e($j['slug']) ?>">Read the full description</a></div>
    <div class="side-card"><h4>What happens next</h4><ol class="mini-steps"><?php foreach (HIRING_STEPS as $i => [$h, $t]): ?><li><span><?= $i + 1 ?></span><div><b><?= e($h) ?></b></div></li><?php endforeach; ?></ol></div>
  </aside>
<?php endif; ?>
</div></section>
<script>
document.querySelectorAll('.sr-file').forEach(function(i){i.addEventListener('change',function(){var n=document.getElementById(i.id+'-name');if(n)n.textContent=i.files.length?(i.files.length>1?i.files.length+' files selected':i.files[0].name):'Choose a file'})});
document.querySelectorAll('.count').forEach(function(c){var t=document.getElementById(c.dataset.for),m=t.getAttribute('maxlength');function u(){c.textContent=t.value.length+' / '+m}t.addEventListener('input',u);u()});
</script>
<?php include __DIR__ . '/inc/foot.php'; ?>
