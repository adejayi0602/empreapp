<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('staff.manage');
$me = admin_user();
$edit = isset($_GET['id']) ? q1("SELECT * FROM admins WHERE id=?", [(int)$_GET['id']]) : null;
$supers = fn() => (int)qv("SELECT COUNT(*) FROM admins WHERE role='super_admin' AND active=1");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = post('action'); $id = (int)post('id');
    $target = $id ? q1("SELECT * FROM admins WHERE id=?", [$id]) : null;
    $name = line(post('name', 80)); $email = strtolower(line(post('email', 150)));
    $title = in_array(post('title'), TITLES, true) ? post('title') : ''; $role = array_key_exists(post('role'), ROLES) ? post('role') : 'intake';
    $pw = (string)($_POST['password'] ?? '');
    switch ($a) {
    case 'add':
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Enter a name and a valid email.', 'err');
        elseif (strlen($pw) < 10) flash('Password must be at least 10 characters.', 'err');
        elseif (q1("SELECT 1 FROM admins WHERE email=?", [$email])) flash('That email already has an account.', 'err');
        else {
            $nid = run("INSERT INTO admins(name,email,password_hash,title,role,active,created_at) VALUES(?,?,?,?,?,1,?)", [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $title, $role, now()]);
            log_act('staff', $nid, "Account created: $name · $title · " . role_label($role));
            send_mail($email, 'Your Empre Technologies admin account', "Hello $name,\n\nAn admin account has been created for you.\n\nAccess level: " . role_label($role) . "\nSign in: " . (($_SERVER['HTTPS'] ?? '') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . dirname(dirname($_SERVER['SCRIPT_NAME'])) . "/admin/login.php\nEmail: $email\n\nYour Super Admin will share your temporary password. Please change it after your first sign-in (My account).");
            flash("Staff account created for $name. Share the password with them securely."); }
        break;
    case 'save':
        if (!$target) break;
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Enter a name and a valid email.', 'err'); break; }
        if (q1("SELECT 1 FROM admins WHERE email=? AND id<>?", [$email, $id])) { flash('Another account already uses that email.', 'err'); break; }
        $active = isset($_POST['active']) ? 1 : 0;
        if ($target['role'] === 'super_admin' && ($role !== 'super_admin' || !$active) && $supers() <= 1) { flash('There must always be at least one active Super Admin.', 'err'); break; }
        if ($id === (int)$me['id'] && (!$active || $role !== $target['role'])) { flash('You cannot suspend or change the access level of your own account.', 'err'); break; }
        run("UPDATE admins SET name=?,email=?,title=?,role=?,active=? WHERE id=?", [$name, $email, $title, $role, $active, $id]);
        if ($pw !== '') { if (strlen($pw) < 10) { flash('Saved, but the new password was NOT changed: it must be at least 10 characters.', 'err'); redirect("staff.php?id=$id"); } run("UPDATE admins SET password_hash=? WHERE id=?", [password_hash($pw, PASSWORD_DEFAULT), $id]); }
        $ch = [];
        if ($role !== $target['role']) $ch[] = 'Access level: ' . role_label($role);
        if ($title !== $target['title']) $ch[] = 'Title: ' . ($title ?: '(none)');
        if ($active !== (int)$target['active']) $ch[] = $active ? 'Re-activated' : 'Suspended';
        if ($pw !== '' && strlen($pw) >= 10) $ch[] = 'Password reset';
        log_act('staff', $id, $ch ? implode(' · ', $ch) : 'Details updated');
        if (!$active) {   // a suspended person cannot keep tasks: put them back in the queue
            foreach (q("SELECT id FROM tasks WHERE assigned_to=? AND status='In Progress'", [$id]) as $t) task_assign((int)$t['id'], null);
        }
        flash('Staff member saved.'); redirect('staff.php');
    case 'delete':
        if (!$target) break;
        if ($id === (int)$me['id']) { flash('You cannot delete your own account.', 'err'); break; }
        if ($target['role'] === 'super_admin' && $supers() <= 1) { flash('Cannot delete the last Super Admin.', 'err'); break; }
        foreach (q("SELECT id FROM tasks WHERE assigned_to=? AND status='In Progress'", [$id]) as $t) task_assign((int)$t['id'], null);
        run("UPDATE tasks SET assigned_to=NULL WHERE assigned_to=?", [$id]);
        run("DELETE FROM admins WHERE id=?", [$id]); flash($target['name'] . ' removed. Their open tasks are now Not Assigned.');
        break;
    }
    redirect('staff.php');
}
$staff = q("SELECT a.*, (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to=a.id AND t.status='In Progress') open_tasks,
                   (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to=a.id AND t.status='Completed') done_tasks FROM admins a ORDER BY a.active DESC, a.name");
admin_head($edit ? 'Edit staff member' : 'Staff & access', 'staff');

if ($edit): ?>
<p><a href="staff.php" class="muted">← All staff</a></p>
<form method="post" class="card" style="max-width:780px"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?>">
<div class="row"><div class="f"><label>Full name</label><input name="name" required value="<?= e($edit['name']) ?>"></div><div class="f"><label>Email (sign-in)</label><input type="email" name="email" required value="<?= e($edit['email']) ?>"></div></div>
<div class="row"><div class="f"><label>Job title</label><select name="title"><option value="">—</option><?= opts(TITLES, (string)$edit['title']) ?></select></div>
<div class="f"><label>Access level</label><select name="role"><?php foreach (ROLES as $k => $r): ?><option value="<?= $k ?>" <?= $edit['role'] === $k ? 'selected' : '' ?>><?= e($r['label']) ?></option><?php endforeach; ?></select></div></div>
<div class="f"><label>Set a new password (leave blank to keep the current one)</label><input type="password" name="password" minlength="10" autocomplete="new-password"></div>
<div class="f"><label style="display:flex;gap:10px;align-items:center;font-weight:600"><input type="checkbox" name="active" style="width:auto" <?= $edit['active'] ? 'checked' : '' ?>> Account is active (untick to suspend sign-in)</label></div>
<button class="btn">Save changes</button> <a class="btn ghost" href="staff.php">Cancel</a></form>
<?php activity_box('staff', (int)$edit['id']); admin_foot(); exit; endif; ?>

<div class="card"><h3>Access levels</h3><div class="tw"><table><tbody>
<?php foreach (ROLES as $k => $r): ?><tr><td style="white-space:nowrap"><b><?= e($r['label']) ?></b></td><td class="muted"><?= e($r['desc']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="muted small" style="margin:12px 0 0">Job titles (CEO, Sales, Facilitator…) are labels used when assigning tasks. What someone can <i>do</i> is decided by their access level. Every staff member can see and update the tasks assigned to them.</p></div>

<div class="card"><h3>Staff (<?= count($staff) ?>)</h3><div class="tw"><table><thead><tr><th>Name</th><th>Title</th><th>Access level</th><th>Tasks</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($staff as $s): ?><tr><td><b><?= e($s['name']) ?></b><?= (int)$s['id'] === (int)$me['id'] ? ' <span class="muted small">(you)</span>' : '' ?><br><span class="muted small"><?= e($s['email']) ?></span></td>
<td><?= e($s['title'] ?: '—') ?></td><td><?= e(role_label($s['role'])) ?></td>
<td class="small"><?= (int)$s['open_tasks'] ?> in progress<br><span class="muted"><?= (int)$s['done_tasks'] ?> completed</span></td>
<td class="muted small"><?= e(dt($s['last_login'], 'd M Y, H:i')) ?></td>
<td><?= badge($s['active'] ? 'Active' : 'Suspended') ?></td>
<td class="num"><div class="actions"><a class="btn sm ghost" href="staff.php?id=<?= $s['id'] ?>">Edit</a>
<?php if ((int)$s['id'] !== (int)$me['id']): ?><form method="post" data-confirm="Remove <?= e($s['name']) ?>? Their open tasks will become Not Assigned."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn sm danger">Remove</button></form><?php endif; ?></div></td></tr>
<?php endforeach; ?></tbody></table></div></div>

<form method="post" class="card" style="max-width:780px"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h3>Add a staff member</h3>
<div class="row"><div class="f"><label>Full name</label><input name="name" required></div><div class="f"><label>Email (used to sign in)</label><input type="email" name="email" required></div></div>
<div class="row"><div class="f"><label>Job title</label><select name="title"><?= opts(TITLES) ?></select></div>
<div class="f"><label>Access level</label><select name="role"><?php foreach (ROLES as $k => $r): if ($k === 'super_admin') continue; ?><option value="<?= $k ?>"><?= e($r['label']) ?></option><?php endforeach; ?><option value="super_admin">Super Admin (full access)</option></select></div></div>
<div class="f"><label>Temporary password (min. 10 characters)</label><input type="password" name="password" required minlength="10" autocomplete="new-password"></div>
<button class="btn">Create account</button></form>
<?php admin_foot();
