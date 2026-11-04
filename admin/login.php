<?php
require __DIR__ . '/../lib/bootstrap.php';
if (!qv("SELECT COUNT(*) FROM admins")) redirect('setup.php');
if (isset($_SESSION['admin'])) redirect('index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fails = $_SESSION['fails'] ?? 0;
    if ($fails >= 5 && time() - ($_SESSION['fail_t'] ?? 0) < 300) $err = 'Too many attempts. Please wait 5 minutes.';
    else {
        $u = q1("SELECT * FROM admins WHERE email=?", [strtolower(line(post('email', 150)))]);
        if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email']];
            unset($_SESSION['fails']); redirect('index.php');
        }
        $_SESSION['fails'] = ($fails >= 5 ? 0 : $fails) + 1; $_SESSION['fail_t'] = time();
        usleep(600000); $err = 'Incorrect email or password.';
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Sign in | Empre Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/admin.css"></head><body>
<div class="login"><form class="box" method="post"><img src="../assets/img/logo-icon.png" alt=""><h1>Admin sign in</h1><p>Empre Technologies dashboard</p>
<?php foreach ($_SESSION['flash'] ?? [] as [$t, $m]) echo '<div class="flash ' . e($t) . '">' . e($m) . '</div>'; unset($_SESSION['flash']); ?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?= csrf_field() ?>
<div class="f"><label>Email</label><input type="email" name="email" required autofocus></div>
<div class="f"><label>Password</label><input type="password" name="password" required></div>
<button class="btn" style="width:100%">Sign in</button></form></div></body></html>
