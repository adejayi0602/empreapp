<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Applicant portal | Empre Technologies Careers'; $desc = 'Sign in to track your job application.'; $active = 'careers.php';
if (applicant_user()) redirect(safe_next((string)($_GET['next'] ?? ''), 'portal.php'));
$next = safe_next((string)($_GET['next'] ?? $_POST['next'] ?? ''), 'portal.php');
$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = line(post('email', 150)); $pw = (string)($_POST['password'] ?? '');
    $key = 'login:' . client_ip() . ':' . strtolower($email);
    if (throttled($key, 8, 900)) $error = 'Too many attempts. Please wait 15 minutes and try again.';
    else {
        $a = q1("SELECT * FROM applicants WHERE email=?", [$email]);
        $hash = $a['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuF7wQXn0v3v0m3kE5m6bQ0y1rJzK3gS2';   // dummy hash keeps timing similar
        if ($a && password_verify($pw, $hash)) { applicant_login((int)$a['id']); redirect($next); }
        throttle_hit($key); $error = 'We could not sign you in. Check your email and password.';
    }
}
include __DIR__ . '/inc/head.php';
?>
<section class="section"><div class="container"><div class="auth-card">
  <div class="auth-ico"><?= svg_icon('lock') ?></div>
  <h1>Applicant portal</h1><p>Sign in with the email and password you created when you applied to follow your application.</p>
  <?php foreach ($_SESSION['flash'] ?? [] as [$t, $m]): ?><div class="alert <?= $t === 'ok' ? 'ok' : 'fail' ?>"><?= e($m) ?></div><?php endforeach; unset($_SESSION['flash']); ?>
  <?php if ($error): ?><div class="alert fail"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="portal-login.php"><?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required autofocus></div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Sign in <?= svg_icon('arrow') ?></button>
  </form>
  <p class="help">Forgotten your password? Email <a href="mailto:<?= e(careers_inbox()) ?>"><?= e(careers_inbox()) ?></a> with your reference number and we will reset it.</p>
  <p class="help">Not applied yet? <a href="careers.php">Browse open roles</a></p>
</div></div></section>
<?php include __DIR__ . '/inc/foot.php'; ?>
