<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'My applications | Empre Technologies Careers'; $desc = 'Track your job applications.'; $active = 'careers.php';
$me = require_applicant();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'password') {
    csrf_check();
    $row = q1("SELECT password_hash FROM applicants WHERE id=?", [$me['id']]); $new = (string)($_POST['new'] ?? '');
    if (!password_verify((string)($_POST['current'] ?? ''), $row['password_hash'])) flash('Your current password is not correct.', 'err');
    elseif (strlen($new) < 8) flash('Choose a new password of at least 8 characters.', 'err');
    elseif ($new !== (string)($_POST['new2'] ?? '')) flash('The new passwords do not match.', 'err');
    else { run("UPDATE applicants SET password_hash=? WHERE id=?", [password_hash($new, PASSWORD_DEFAULT), $me['id']]); flash('Password changed.'); }
    redirect('portal.php#account');
}
$apps = q("SELECT a.*, j.title jtitle, j.company jcompany, j.slug jslug, j.status jstatus, j.closing_date jclosing FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.applicant_id=? ORDER BY a.id DESC", [$me['id']]);
include __DIR__ . '/inc/head.php';
$need = array_filter($apps, fn($a) => $a['status'] === 'More Details Needed');
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><a href="careers.php">Careers</a><span>/</span><span class="cur">My applications</span></div>
  <h1>Hello, <?= e(explode(' ', $me['name'])[0]) ?></h1><p>Follow every application in one place and respond when the hiring team asks for more.</p>
</div></section>
<section class="section"><div class="container portal-wrap">
  <?php foreach ($_SESSION['flash'] ?? [] as [$t, $m]): ?><div class="alert <?= $t === 'ok' ? 'ok' : 'fail' ?>"><?= e($m) ?></div><?php endforeach; unset($_SESSION['flash']); ?>
  <?php if ($need): ?><div class="alert warn"><b>Action needed:</b> the hiring team asked for more details on <?= count($need) ?> application<?= count($need) > 1 ? 's' : '' ?>. Open it below to update.</div><?php endif; ?>
  <div class="portal-grid">
    <div>
      <h2 class="h2s" style="font-size:26px;margin-top:0">Your applications</h2>
      <?php foreach ($apps as $a): [$hl, , $tone] = app_status_info($a['status']); $js = job_state(['status' => $a['jstatus'], 'closing_date' => $a['jclosing']]); ?>
      <a class="app-card tone-<?= e($tone) ?>" href="portal-app.php?id=<?= (int)$a['id'] ?>">
        <div class="ac-main"><b><?= e($a['jtitle']) ?></b><small><?= e($a['jcompany']) ?> · <?= e($a['code']) ?> · Applied <?= e(dt($a['created_at'])) ?></small></div>
        <div class="ac-state"><span class="st st-<?= e(strtolower(str_replace(' ', '-', $a['status']))) ?>"><?= e($a['status']) ?></span><small>Role: <?= e($js) ?></small></div>
        <span class="ac-go"><?= svg_icon('arrow') ?></span>
      </a>
      <?php endforeach; if (!$apps): ?><div class="empty-roles"><h3>No applications yet</h3><p>Find a role that fits you and apply in minutes.</p><a class="btn btn-primary" href="careers.php">Browse open roles <?= svg_icon('arrow') ?></a></div><?php endif; ?>
      <?php if ($apps): ?><p style="margin-top:22px"><a class="btn btn-dark" href="careers.php">Browse more roles <?= svg_icon('arrow') ?></a></p><?php endif; ?>
    </div>
    <aside class="side-card" id="account"><h4>My account</h4><p class="muted small"><?= e($me['name']) ?><br><?= e($me['email']) ?></p>
      <details><summary>Change password</summary>
        <form method="post" action="portal.php"><?= csrf_field() ?><input type="hidden" name="action" value="password">
          <div class="field"><label>Current password</label><input type="password" name="current" autocomplete="current-password" required></div>
          <div class="field"><label>New password</label><input type="password" name="new" minlength="8" autocomplete="new-password" required></div>
          <div class="field"><label>Repeat new password</label><input type="password" name="new2" minlength="8" autocomplete="new-password" required></div>
          <button class="btn btn-dark btn-sm">Update password</button></form></details>
      <form method="post" action="portal-logout.php" style="margin-top:14px"><?= csrf_field() ?><button class="btn btn-outline-dark btn-sm">Sign out</button></form>
    </aside>
  </div>
</div></section>
<?php include __DIR__ . '/inc/foot.php'; ?>
