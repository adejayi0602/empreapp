<?php
require __DIR__ . '/../lib/admin_ui.php';
if (qv("SELECT COUNT(*) FROM admins")) redirect('login.php');   // setup only works once, when there is no admin yet
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = line(post('name', 80)); $email = line(post('email', 150)); $pw = (string)($_POST['password'] ?? '');
    if (mb_strlen($name) < 2) $err = 'Enter your name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
    elseif (strlen($pw) < 10) $err = 'Use a password of at least 10 characters.';
    elseif ($pw !== (string)($_POST['password2'] ?? '')) $err = 'Passwords do not match.';
    else {
        $title = in_array(post('title'), TITLES, true) ? post('title') : 'CEO';
        run("INSERT INTO admins(name,email,password_hash,title,role,active,created_at) VALUES(?,?,?,?,'super_admin',1,?)", [$name, strtolower($email), password_hash($pw, PASSWORD_DEFAULT), $title, now()]);
        flash('Admin account created. Please sign in.'); redirect('login.php');
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Setup | Empre Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/admin.css"></head><body>
<div class="login"><form class="box" method="post"><img src="../assets/img/logo-icon.png" alt=""><h1>Create Super Admin account</h1><p>First-time setup. This page disappears once an admin exists.</p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?= csrf_field() ?>
<div class="f"><label>Your name</label><input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div>
<div class="f"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
<div class="f"><label>Your job title</label><select name="title"><?= opts(TITLES, 'CEO') ?></select></div>
<div class="f"><label>Password (min. 10 characters)</label><input type="password" name="password" required minlength="10"></div>
<div class="f"><label>Confirm password</label><input type="password" name="password2" required></div>
<button class="btn" style="width:100%">Create account</button></form></div></body></html>
