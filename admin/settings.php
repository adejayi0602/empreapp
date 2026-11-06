<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
$me = admin_user(); $isSuper = can('settings.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    switch (post('action')) {
    case 'company':
        require_perm('settings.manage');
        foreach (['company_name', 'company_email', 'company_phone', 'company_web', 'currency', 'bank_name', 'account_name', 'account_number'] as $k) save_setting($k, line(post($k, 150)));
        save_setting('company_address', post('company_address', 300)); save_setting('invoice_note', post('invoice_note', 500));
        flash('Settings saved. They appear on all invoices and receipts.'); break;
    case 'profile':
        $name = line(post('name', 80));
        if (mb_strlen($name) < 2) flash('Enter your name.', 'err');
        else { run("UPDATE admins SET name=? WHERE id=?", [$name, $me['id']]); flash('Name updated.'); }
        break;
    case 'password':
        $u = q1("SELECT * FROM admins WHERE id=?", [$me['id']]);
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) flash('Current password is incorrect.', 'err');
        elseif (strlen((string)($_POST['new'] ?? '')) < 10) flash('New password must be at least 10 characters.', 'err');
        elseif ($_POST['new'] !== ($_POST['new2'] ?? '')) flash('New passwords do not match.', 'err');
        else { run("UPDATE admins SET password_hash=? WHERE id=?", [password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $me['id']]); flash('Password changed.'); }
        break;
    }
    redirect('settings.php');
}
$S = settings();
admin_head($isSuper ? 'Settings' : 'My account', 'settings'); ?>
<?php if ($isSuper): ?>
<div class="flash err" id="dbwarn" style="display:none"><b>Security warning:</b> your database file can be downloaded from the web. Move it outside <code>public_html</code> and update <code>DB_PATH</code> in <code>config.php</code> (see README, "Security").</div>
<script>fetch('../data/<?= e(basename(DB_PATH)) ?>',{cache:'no-store'}).then(function(r){if(r.ok)return r.text().then(function(t){if(t.indexOf('SQLite format 3')===0)document.getElementById('dbwarn').style.display='block'})}).catch(function(){});</script>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="company"><h3>Company &amp; payment details (shown on invoices and receipts)</h3>
<div class="row"><div class="f"><label>Company name</label><input name="company_name" value="<?= e($S['company_name']) ?>"></div><div class="f"><label>Currency symbol</label><input name="currency" value="<?= e($S['currency']) ?>"></div></div>
<div class="row3 row"><div class="f"><label>Email</label><input name="company_email" value="<?= e($S['company_email']) ?>"></div><div class="f"><label>Phone</label><input name="company_phone" value="<?= e($S['company_phone']) ?>"></div><div class="f"><label>Website</label><input name="company_web" value="<?= e($S['company_web']) ?>"></div></div>
<div class="f"><label>Address</label><textarea name="company_address" style="min-height:60px"><?= e($S['company_address']) ?></textarea></div>
<div class="row3 row"><div class="f"><label>Bank name</label><input name="bank_name" value="<?= e($S['bank_name']) ?>"></div><div class="f"><label>Account name</label><input name="account_name" value="<?= e($S['account_name']) ?>"></div><div class="f"><label>Account number</label><input name="account_number" value="<?= e($S['account_number']) ?>"></div></div>
<div class="f"><label>Note printed on invoices</label><textarea name="invoice_note" style="min-height:60px"><?= e($S['invoice_note']) ?></textarea></div><button class="btn">Save settings</button></form>
<?php endif; ?>
<div class="grid g2">
<div class="card"><h3>My profile</h3><dl class="kv" style="margin-bottom:16px"><dt>Email</dt><dd><?= e($me['email']) ?></dd><dt>Job title</dt><dd><?= e($me['title'] ?: '—') ?></dd><dt>Access level</dt><dd><?= e(role_label($me['role'])) ?></dd></dl>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><div class="f"><label>Display name</label><input name="name" required value="<?= e($me['name']) ?>"></div><button class="btn dark">Update name</button></form>
<p class="muted small" style="margin:14px 0 0">Your title and access level are set by the Super Admin.</p></div>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="password"><h3>Change my password</h3>
<div class="f"><label>Current password</label><input type="password" name="current" required></div><div class="f"><label>New password (10+ characters)</label><input type="password" name="new" required minlength="10"></div><div class="f"><label>Confirm new password</label><input type="password" name="new2" required></div><button class="btn dark">Change password</button></form>
</div>
<?php admin_foot();
