<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
$c = fn($sql) => (int)qv($sql);
$newContacts = $c("SELECT COUNT(*) FROM contacts WHERE status='New'");
$openContacts = $c("SELECT COUNT(*) FROM contacts WHERE status IN ('New','Contacted','Pending')");
$students = $c("SELECT COUNT(*) FROM students");
$applied = $c("SELECT COUNT(*) FROM students WHERE status='Applied'");
$enrolled = $c("SELECT COUNT(*) FROM students WHERE status='Enrolled'");
$revenue = (float)qv("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Confirmed'");
$pendingPay = $c("SELECT COUNT(*) FROM payments WHERE status='Pending'");
$outstanding = 0.0;
foreach (q("SELECT * FROM students WHERE status NOT IN ('Withdrawn')") as $s) { $t = student_totals($s); if ($t['balance'] > 0) $outstanding += $t['balance']; }
$today = date('Y-m-d');
$followups = q("SELECT 'contact' t,id,name,next_action,follow_up FROM contacts WHERE follow_up<>'' AND follow_up<=? AND status NOT IN ('Enrolled','Closed')
                UNION ALL SELECT 'student',id,name,next_action,follow_up FROM students WHERE follow_up<>'' AND follow_up<=? AND status NOT IN ('Completed','Withdrawn') ORDER BY follow_up LIMIT 10", [$today, $today]);
$recentS = q("SELECT * FROM students ORDER BY id DESC LIMIT 6");
$recentC = q("SELECT * FROM contacts ORDER BY id DESC LIMIT 6");
admin_head('Dashboard', 'dashboard'); ?>
<div class="grid g4" style="margin-bottom:18px">
  <div class="card stat hl"><small>New contacts</small><b><?= $newContacts ?></b><span><?= $openContacts ?> open in total</span></div>
  <div class="card stat"><small>Students</small><b><?= $students ?></b><span><?= $applied ?> awaiting review · <?= $enrolled ?> enrolled</span></div>
  <div class="card stat"><small>Payments received</small><b><?= e(money($revenue)) ?></b><span><?= $pendingPay ?> payment(s) awaiting confirmation</span></div>
  <div class="card stat"><small>Outstanding balance</small><b><?= e(money($outstanding)) ?></b><span>Across active students</span></div>
</div>
<?php if ($followups): ?><div class="card"><h3>Follow-ups due</h3><div class="tw"><table><tbody>
<?php foreach ($followups as $f): ?><tr><td><?= badge($f['t'] === 'contact' ? 'Contact' : 'Student') ?></td><td><b><?= e($f['name']) ?></b></td><td><?= e($f['next_action'] ?: '—') ?></td><td class="muted"><?= e(dt($f['follow_up'])) ?></td><td class="num"><a class="btn sm ghost" href="<?= $f['t'] === 'contact' ? 'contact' : 'student' ?>.php?id=<?= (int)$f['id'] ?>">Open</a></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php endif; ?>
<div class="grid g2">
  <div class="card"><h3>Latest applications</h3><div class="tw"><table><thead><tr><th>Student</th><th>Course</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($recentS as $s): ?><tr><td><a href="student.php?id=<?= $s['id'] ?>"><b><?= e($s['name']) ?></b></a><br><span class="muted small"><?= e($s['student_code']) ?></span></td><td><?= e($s['course_title']) ?></td><td><?= badge($s['status']) ?></td></tr><?php endforeach; ?>
  <?php if (!$recentS): ?><tr><td class="muted">No applications yet.</td></tr><?php endif; ?></tbody></table></div><p style="margin:14px 0 0"><a href="students.php" class="btn sm ghost">All students</a></p></div>
  <div class="card"><h3>Latest contacts</h3><div class="tw"><table><thead><tr><th>Name</th><th>Interest</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($recentC as $s): ?><tr><td><a href="contact.php?id=<?= $s['id'] ?>"><b><?= e($s['name']) ?></b></a><br><span class="muted small"><?= e(dt($s['created_at'])) ?></span></td><td><?= e($s['service'] ?: 'General') ?></td><td><?= badge($s['status']) ?></td></tr><?php endforeach; ?>
  <?php if (!$recentC): ?><tr><td class="muted">No contacts yet.</td></tr><?php endif; ?></tbody></table></div><p style="margin:14px 0 0"><a href="contacts.php" class="btn sm ghost">All contacts</a></p></div>
</div>
<?php admin_foot();
