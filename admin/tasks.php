<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
$me = admin_user(); $all = can('tasks.viewall');
$view = $_GET['view'] ?? 'mine';
if (!$all) $view = 'mine';
if (!in_array($view, ['mine', 'all', 'unassigned'], true)) $view = 'mine';
$fs = $_GET['status'] ?? ''; $fa = (int)($_GET['assignee'] ?? 0); $fq = trim($_GET['q'] ?? '');
$where = []; $p = [];
if ($view === 'mine') { $where[] = 't.assigned_to=?'; $p[] = $me['id']; }
if ($view === 'unassigned') { $where[] = "t.status='Not Assigned'"; }
if (in_array($fs, TASK_STATUSES, true)) { $where[] = 't.status=?'; $p[] = $fs; }
if ($fa && $view === 'all') { $where[] = 't.assigned_to=?'; $p[] = $fa; }
if ($fq !== '') { $where[] = "(t.title LIKE ? OR s.name LIKE ? OR s.student_code LIKE ?)"; array_push($p, "%$fq%", "%$fq%", "%$fq%"); }
$rows = q("SELECT t.*, s.name sname, s.student_code scode, a.name aname, a.title atitle FROM tasks t
           LEFT JOIN students s ON s.id=t.student_id LEFT JOIN admins a ON a.id=t.assigned_to" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
          " ORDER BY CASE t.status WHEN 'Not Assigned' THEN 0 WHEN 'In Progress' THEN 1 ELSE 2 END, COALESCE(NULLIF(t.due_date,''),'9999'), t.id DESC LIMIT 300", $p);
$mineOpen = (int)qv("SELECT COUNT(*) FROM tasks WHERE assigned_to=? AND status='In Progress'", [$me['id']]);
$unassigned = $all ? (int)qv("SELECT COUNT(*) FROM tasks WHERE status='Not Assigned'") : 0;
$today = date('Y-m-d');
admin_head($view === 'mine' ? 'My tasks' : 'Tasks', 'tasks'); ?>
<div class="tabs"><a href="tasks.php" class="<?= $view === 'mine' ? 'on' : '' ?>">My tasks<i><?= $mineOpen ?></i></a>
<?php if ($all): ?><a href="?view=unassigned" class="<?= $view === 'unassigned' ? 'on' : '' ?>">Not assigned<i><?= $unassigned ?></i></a><a href="?view=all" class="<?= $view === 'all' ? 'on' : '' ?>">All tasks</a><?php endif; ?></div>
<form class="bar" method="get"><input type="hidden" name="view" value="<?= e($view) ?>">
<input class="grow" name="q" placeholder="Search task, student name or ID…" value="<?= e($fq) ?>">
<?php if ($view !== 'unassigned'): ?><select name="status"><option value="">Any status</option><?= opts(TASK_STATUSES, $fs) ?></select><?php endif; ?>
<?php if ($view === 'all'): ?><select name="assignee"><option value="">Anyone</option><?= staff_options($fa ?: null, 'Anyone') ?></select><?php endif; ?>
<button class="btn dark">Filter</button></form>
<div class="card"><div class="tw"><table><thead><tr><th>Task</th><th>Student</th><th>Assigned to</th><th>Status</th><th>Due</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $t): $late = $t['due_date'] && $t['status'] === 'In Progress' && $t['due_date'] < $today; ?><tr>
<td><a href="task.php?id=<?= $t['id'] ?>"><b><?= e($t['title']) ?></b></a></td>
<td><?= $t['student_id'] ? e($t['sname']) . '<br><span class="muted small">' . e($t['scode']) . '</span>' : '<span class="muted">—</span>' ?></td>
<td><?= $t['aname'] ? e($t['aname']) . '<br><span class="muted small">' . e($t['atitle']) . '</span>' : '<span class="muted">Nobody yet</span>' ?></td>
<td><?= badge($t['status']) ?></td>
<td class="small <?= $late ? '' : 'muted' ?>" style="<?= $late ? 'color:#9B2C20;font-weight:700' : '' ?>"><?= $t['due_date'] ? e(dt($t['due_date'])) . ($late ? '<br>Overdue' : '') : '—' ?></td>
<td class="num"><a class="btn sm ghost" href="task.php?id=<?= $t['id'] ?>"><?= is_super() && $t['status'] === 'Not Assigned' ? 'Assign' : 'Open' ?></a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted"><?= $view === 'mine' ? 'You have no tasks right now. 🎉' : 'No tasks found.' ?></td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
