<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
$fs = $_GET['status'] ?? ''; $fc = (int)($_GET['course'] ?? 0); $fq = trim($_GET['q'] ?? '');
$where = []; $p = [];
if (in_array($fs, STUDENT_STATUSES, true)) { $where[] = 's.status=?'; $p[] = $fs; }
if ($fc) { $where[] = 's.course_id=?'; $p[] = $fc; }
if ($fq !== '') { $where[] = "(s.name LIKE ? OR s.student_code LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.location LIKE ?)"; array_push($p, "%$fq%", "%$fq%", "%$fq%", "%$fq%", "%$fq%"); }
$rows = q("SELECT s.* FROM students s" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY s.id DESC LIMIT 500", $p);
$counts = array_column(q("SELECT status, COUNT(*) n FROM students GROUP BY status"), 'n', 'status');
$courses = q("SELECT id,title FROM courses ORDER BY sort,title");
admin_head('Students & applications', 'students'); ?>
<div class="tabs"><a href="students.php" class="<?= $fs === '' ? 'on' : '' ?>">All<i><?= array_sum($counts) ?></i></a>
<?php foreach (STUDENT_STATUSES as $s): ?><a href="?status=<?= urlencode($s) ?>" class="<?= $fs === $s ? 'on' : '' ?>"><?= e($s) ?><i><?= (int)($counts[$s] ?? 0) ?></i></a><?php endforeach; ?></div>
<form class="bar" method="get"><?php if ($fs): ?><input type="hidden" name="status" value="<?= e($fs) ?>"><?php endif; ?>
<input class="grow" name="q" placeholder="Search name, student ID, email, phone, location…" value="<?= e($fq) ?>">
<select name="course"><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $fc === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select>
<button class="btn dark">Filter</button><a class="btn" href="student-new.php">+ Add student</a></form>
<div class="card"><div class="tw"><table><thead><tr><th>Student ID</th><th>Name</th><th>Course</th><th>Applied</th><th>Status</th><th>Payment</th><th class="num">Balance</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $s): $t = student_totals($s); ?><tr>
<td><span class="muted small"><b><?= e($s['student_code']) ?></b></span></td>
<td><a href="student.php?id=<?= $s['id'] ?>"><b><?= e($s['name']) ?></b></a><br><span class="muted small"><?= e($s['email']) ?></span></td>
<td><?= e($s['course_title']) ?></td><td class="muted small"><?= e(dt($s['created_at'])) ?></td>
<td><?= badge($s['status']) ?></td><td><?= badge($t['label']) ?><br><span class="muted small"><?= e($s['plan']) ?></span></td>
<td class="num"><?= $t['due'] > 0 ? e(money(max(0, $t['balance']))) : '—' ?></td>
<td class="num"><a class="btn sm ghost" href="student.php?id=<?= $s['id'] ?>">Manage</a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="muted">No students found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
