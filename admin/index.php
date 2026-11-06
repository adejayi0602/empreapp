<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
if (!can('dashboard')) redirect(home_page());     // e.g. Intake Officers land on "Add student"
$me = admin_user();
$c = fn($sql, $p = []) => (int)qv($sql, $p);
$cards = [];
if (can('contacts.view')) {
    $cards[] = ['hl', 'New contacts', $c("SELECT COUNT(*) FROM contacts WHERE status='New'"), $c("SELECT COUNT(*) FROM contacts WHERE status IN ('New','Contacted','Pending')") . ' open in total'];
}
if (can('students.view')) {
    $cards[] = ['', 'Students', $c("SELECT COUNT(*) FROM students"), $c("SELECT COUNT(*) FROM students WHERE status='Applied'") . ' awaiting review · ' . $c("SELECT COUNT(*) FROM students WHERE status='Enrolled'") . ' enrolled'];
}
if (can('billing.view')) {
    $revenue = (float)qv("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Confirmed'");
    $outstanding = 0.0;
    foreach (q("SELECT * FROM students WHERE status NOT IN ('Withdrawn')") as $s) { $t = student_totals($s); if ($t['balance'] > 0) $outstanding += $t['balance']; }
    $cards[] = ['', 'Payments received', money($revenue), $c("SELECT COUNT(*) FROM payments WHERE status='Pending'") . ' payment(s) awaiting confirmation'];
    $cards[] = ['', 'Outstanding balance', money($outstanding), 'Across active students'];
}
$unassigned = can('tasks.viewall') ? q("SELECT t.*, s.name sname, s.student_code scode FROM tasks t LEFT JOIN students s ON s.id=t.student_id WHERE t.status='Not Assigned' ORDER BY t.id DESC LIMIT 8") : [];
$mine = q("SELECT t.*, s.name sname, s.student_code scode FROM tasks t LEFT JOIN students s ON s.id=t.student_id WHERE t.assigned_to=? AND t.status='In Progress' ORDER BY COALESCE(NULLIF(t.due_date,''),'9999'), t.id LIMIT 8", [$me['id']]);
$today = date('Y-m-d');
$followups = can('contacts.view') ? q("SELECT 'contact' t,id,name,next_action,follow_up FROM contacts WHERE follow_up<>'' AND follow_up<=? AND status NOT IN ('Enrolled','Closed')
                UNION ALL SELECT 'student',id,name,next_action,follow_up FROM students WHERE follow_up<>'' AND follow_up<=? AND status NOT IN ('Completed','Withdrawn') ORDER BY follow_up LIMIT 10", [$today, $today]) : [];
$recentS = can('students.view') ? q("SELECT * FROM students ORDER BY id DESC LIMIT 6") : [];
$recentC = can('contacts.view') ? q("SELECT * FROM contacts ORDER BY id DESC LIMIT 6") : [];
admin_head('Dashboard', 'dashboard'); ?>
<p class="muted" style="margin-top:0">Welcome back, <b style="color:var(--ink)"><?= e(explode(' ', $me['name'])[0]) ?></b> · <?= e(($me['title'] ? $me['title'] . ' · ' : '') . role_label($me['role'])) ?></p>
<?php if ($cards): ?><div class="grid g4" style="margin-bottom:18px">
<?php foreach ($cards as [$cls, $label, $val, $sub]): ?><div class="card stat <?= $cls ?>"><small><?= e($label) ?></small><b><?= e($val) ?></b><span><?= e($sub) ?></span></div><?php endforeach; ?></div><?php endif; ?>

<div class="grid g2" style="margin-bottom:18px">
<?php if (can('tasks.viewall')): ?>
  <div class="card"><h3>Needs assigning <?= $unassigned ? '<span class="b b-not-assigned">' . count($unassigned) . '</span>' : '' ?></h3>
  <?php if ($unassigned): ?><div class="tw"><table><tbody><?php foreach ($unassigned as $k): ?><tr><td><a href="task.php?id=<?= $k['id'] ?>"><b><?= e($k['title']) ?></b></a><br><span class="muted small"><?= e($k['sname'] ?: '') ?> <?= e($k['scode'] ?: '') ?></span></td><td class="num"><a class="btn sm <?= is_super() ? '' : 'ghost' ?>" href="task.php?id=<?= $k['id'] ?>"><?= is_super() ? 'Assign' : 'Open' ?></a></td></tr><?php endforeach; ?></tbody></table></div>
  <?php else: ?><p class="muted">Every task has an owner. ✓</p><?php endif; ?></div>
<?php endif; ?>
  <div class="card"><h3>My tasks in progress</h3>
  <?php if ($mine): ?><div class="tw"><table><tbody><?php foreach ($mine as $k): $late = $k['due_date'] && $k['due_date'] < $today; ?><tr><td><a href="task.php?id=<?= $k['id'] ?>"><b><?= e($k['title']) ?></b></a><br><span class="muted small"><?= e($k['sname'] ?: '') ?> <?= e($k['scode'] ?: '') ?><?= $k['due_date'] ? ' · due ' . e(dt($k['due_date'])) . ($late ? ' (overdue)' : '') : '' ?></span></td><td class="num"><a class="btn sm ghost" href="task.php?id=<?= $k['id'] ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div>
  <?php else: ?><p class="muted">Nothing assigned to you right now.</p><?php endif; ?></div>
</div>

<?php if ($followups): ?><div class="card"><h3>Follow-ups due</h3><div class="tw"><table><tbody>
<?php foreach ($followups as $f): ?><tr><td><?= badge($f['t'] === 'contact' ? 'Contact' : 'Student') ?></td><td><b><?= e($f['name']) ?></b></td><td><?= e($f['next_action'] ?: '—') ?></td><td class="muted"><?= e(dt($f['follow_up'])) ?></td><td class="num"><a class="btn sm ghost" href="<?= $f['t'] === 'contact' ? 'contact' : 'student' ?>.php?id=<?= (int)$f['id'] ?>">Open</a></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php endif; ?>
<div class="grid g2">
<?php if (can('students.view')): ?>
  <div class="card"><h3>Latest applications</h3><div class="tw"><table><thead><tr><th>Student</th><th>Course</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($recentS as $s): ?><tr><td><a href="student.php?id=<?= $s['id'] ?>"><b><?= e($s['name']) ?></b></a><br><span class="muted small"><?= e($s['student_code']) ?></span></td><td><?= e($s['course_title']) ?></td><td><?= badge($s['status']) ?></td></tr><?php endforeach; ?>
  <?php if (!$recentS): ?><tr><td class="muted">No applications yet.</td></tr><?php endif; ?></tbody></table></div><p style="margin:14px 0 0"><a href="students.php" class="btn sm ghost">All students</a></p></div>
<?php endif; if (can('contacts.view')): ?>
  <div class="card"><h3>Latest contacts</h3><div class="tw"><table><thead><tr><th>Name</th><th>Interest</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($recentC as $s): ?><tr><td><a href="contact.php?id=<?= $s['id'] ?>"><b><?= e($s['name']) ?></b></a><br><span class="muted small"><?= e(dt($s['created_at'])) ?></span></td><td><?= e($s['service'] ?: 'General') ?></td><td><?= badge($s['status']) ?></td></tr><?php endforeach; ?>
  <?php if (!$recentC): ?><tr><td class="muted">No contacts yet.</td></tr><?php endif; ?></tbody></table></div><p style="margin:14px 0 0"><a href="contacts.php" class="btn sm ghost">All contacts</a></p></div>
<?php endif; ?>
</div>
<?php admin_foot();
