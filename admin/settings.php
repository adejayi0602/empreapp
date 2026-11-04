<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
$me = admin_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('action')) {
    case 'company':
        foreach (['company_name', 'company_email', 'company_phone', 'company_web', 'currency', 'bank_name', 'account_name', 'account_number'] as $k) save_setting($k, line(post($k, 150)));
        save_setting('company_address', post('company_address', 300)); save_setting('invoice_note', post('invoice_note', 500));
        flash('Settings saved. They appear on all invoices and receipts.'); break;
    case 'password':
        $u = q1("SELECT * FROM admins WHERE id=?", [$me['id']]);
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) flash('Current password is incorrect.', 'err');
        elseif (strlen((string)$_POST['new']) < 10) flash('New password must be at least 10 characters.', 'err');
        elseif ($_POST['new'] !== $_POST['new2']) flash('New passwords do not match.', 'err');
        else { run("UPDATE admins SET password_hash=? WHERE id=?", [password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $me['id']]); flash('Password changed.'); }
        break;
    case 'add_admin':
        $email = strtolower(line(post('email', 150)));
        if (mb_strlen(post('name')) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string)$_POST['password']) < 10) flash('Enter a name, valid email and a password of 10+ characters.', 'err');
        elseif (q1("SELECT 1 FROM admins WHERE email=?", [$email])) flash('That email already has an account.', 'err');
        else { run("INSERT INTO admins(name,email,password_hash,created_at) VALUES(?,?,?,?)", [line(post('name', 80)), $email, password_hash((string)$_POST['password'], PASSWORD_DEFAULT), now()]); flash('Admin user added.'); }
        break;
    case 'del_admin':
        $aid = (int)post('aid');
        if ($aid === (int)$me['id']) flash('You cannot delete your own account.', 'err'); else { run("DELETE FROM admins WHERE id=?", [$aid]); flash('Admin removed.'); }
    }
    redirect('settings.php');
}
$S = settings(); $admins = q("SELECT * FROM admins ORDER BY id");
admin_head('Settings', 'settings'); ?>
<div class="flash err" id="dbwarn" style="display:none"><b>Security warning:</b> your database file can be downloaded from the web. Move it outside <code>public_html</code> and update <code>DB_PATH</code> in <code>config.php</code> (see README, "Security").</div>
<script>fetch('../data/<?= e(basename(DB_PATH)) ?>',{cache:'no-store'}).then(function(r){if(r.ok)return r.text().then(function(t){if(t.indexOf('SQLite format 3')===0)document.getElementById('dbwarn').style.display='block'})}).catch(function(){});</script>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="company"><h3>Company &amp; payment details (shown on invoices and receipts)</h3>
<div class="row"><div class="f"><label>Company name</label><input name="company_name" value="<?= e($S['company_name']) ?>"></div><div class="f"><label>Currency symbol</label><input name="currency" value="<?= e($S['currency']) ?>"></div></div>
<div class="row3 row"><div class="f"><label>Email</label><input name="company_email" value="<?= e($S['company_email']) ?>"></div><div class="f"><label>Phone</label><input name="company_phone" value="<?= e($S['company_phone']) ?>"></div><div class="f"><label>Website</label><input name="company_web" value="<?= e($S['company_web']) ?>"></div></div>
<div class="f"><label>Address</label><textarea name="company_address" style="min-height:60px"><?= e($S['company_address']) ?></textarea></div>
<div class="row3 row"><div class="f"><label>Bank name</label><input name="bank_name" value="<?= e($S['bank_name']) ?>"></div><div class="f"><label>Account name</label><input name="account_name" value="<?= e($S['account_name']) ?>"></div><div class="f"><label>Account number</label><input name="account_number" value="<?= e($S['account_number']) ?>"></div></div>
<div class="f"><label>Note printed on invoices</label><textarea name="invoice_note" style="min-height:60px"><?= e($S['invoice_note']) ?></textarea></div><button class="btn">Save settings</button></form>
<div class="grid g2">
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="password"><h3>Change my password</h3>
<div class="f"><label>Current password</label><input type="password" name="current" required></div><div class="f"><label>New password (10+ characters)</label><input type="password" name="new" required minlength="10"></div><div class="f"><label>Confirm new password</label><input type="password" name="new2" required></div><button class="btn dark">Change password</button></form>
<div class="card"><h3>Admin users</h3><div class="tw"><table><tbody><?php foreach ($admins as $a): ?><tr><td><b><?= e($a['name']) ?></b><br><span class="muted small"><?= e($a['email']) ?></span></td><td class="num"><?php if ((int)$a['id'] !== (int)$me['id']): ?><form method="post" data-confirm="Remove this admin?"><?= csrf_field() ?><input type="hidden" name="action" value="del_admin"><input type="hidden" name="aid" value="<?= $a['id'] ?>"><button class="btn sm danger">Remove</button></form><?php else: ?><span class="muted small">You</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<details style="margin-top:12px"><summary class="btn sm ghost" style="display:inline-block">+ Add admin</summary><form method="post" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="add_admin"><div class="f"><label>Name</label><input name="name" required></div><div class="f"><label>Email</label><input type="email" name="email" required></div><div class="f"><label>Password (10+ characters)</label><input type="password" name="password" required minlength="10"></div><button class="btn dark sm">Add admin</button></form></details></div></div>
<?php admin_foot();
