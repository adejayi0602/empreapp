<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
$me = admin_user();
$mine = fn(array $t) => (int)$t['assigned_to'] === (int)$me['id'];

/* ---------------- create a task (from a student page) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'create') {
    csrf_check(); require_perm('tasks.assign');
    $sid = (int)post('student_id'); $title = line(post('title', 160));
    $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('due_date')) ? post('due_date') : '';
    $assignee = (int)post('assignee') ?: null;
    if ($assignee && !q1("SELECT 1 FROM admins WHERE id=? AND active=1", [$assignee])) $assignee = null;
    if (!q1("SELECT 1 FROM students WHERE id=?", [$sid]) || mb_strlen($title) < 3) { flash('Enter a task title.', 'err'); redirect("student.php?id=$sid"); }
    $tid = create_task($sid, $title, post('details', 2000), $assignee, $due, $me['name']);
    if ($assignee) notify_assignee($tid);
    flash($assignee ? 'Task created and assigned. Status: In Progress.' : 'Task created (Not Assigned).');
    redirect(safe_back("task.php?id=$tid"));
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$t = q1("SELECT t.*, s.name sname, s.student_code scode, s.course_title, s.email semail, s.phone sphone, a.name aname, a.title atitle, a.email aemail
         FROM tasks t LEFT JOIN students s ON s.id=t.student_id LEFT JOIN admins a ON a.id=t.assigned_to WHERE t.id=?", [$id]);
if (!$t) { flash('Task not found.', 'err'); redirect('tasks.php'); }
if (!$mine($t) && !can('tasks.viewall')) deny();

function notify_assignee(int $tid): void {
    $t = q1("SELECT t.*, s.name sname, s.student_code scode, a.name aname, a.email aemail FROM tasks t LEFT JOIN students s ON s.id=t.student_id LEFT JOIN admins a ON a.id=t.assigned_to WHERE t.id=?", [$tid]);
    if (!$t || !$t['aemail']) return;
    send_mail($t['aemail'], 'New task assigned: ' . $t['title'],
        "Hello {$t['aname']},\n\nA task has been assigned to you.\n\nTask: {$t['title']}\n" . ($t['sname'] ? "Student: {$t['sname']} ({$t['scode']})\n" : '') .
        ($t['due_date'] ? 'Due: ' . dt($t['due_date']) . "\n" : '') . ($t['details'] ? "\n{$t['details']}\n" : '') . "\nSign in to the admin dashboard > My tasks to comment and mark it completed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $back = safe_back("task.php?id=$id");
    switch (post('action')) {
    case 'assign':
        require_perm('tasks.assign');
        $to = (int)post('assignee') ?: null;
        if ($to && !q1("SELECT 1 FROM admins WHERE id=? AND active=1", [$to])) { flash('That staff member is not available.', 'err'); break; }
        $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('due_date')) ? post('due_date') : '';
        if ($due !== (string)$t['due_date']) run("UPDATE tasks SET due_date=? WHERE id=?", [$due, $id]);
        if ($to === ((int)$t['assigned_to'] ?: null) && $t['status'] !== 'Completed') { flash('No change in assignment.'); break; }
        task_assign($id, $to);
        if ($to) { notify_assignee($id); flash('Assigned to ' . admin_label($to) . '. Status: In Progress.'); }
        else flash('Task unassigned. Status: Not Assigned.');
        break;
    case 'comment':
        if (!$mine($t) && !can('tasks.viewall')) deny();
        $body = post('body', 2000);
        if (mb_strlen($body) < 2) { flash('Write a comment first.', 'err'); break; }
        run("INSERT INTO task_comments(task_id,admin_id,author,body,created_at) VALUES(?,?,?,?,?)", [$id, $me['id'], $me['name'] . ($me['title'] ? ' (' . $me['title'] . ')' : ''), $body, now()]);
        run("UPDATE tasks SET updated_at=? WHERE id=?", [now(), $id]);
        flash('Comment added.'); break;
    case 'complete':
        if (!$mine($t) && !is_super()) deny();
        if ($t['status'] !== 'In Progress') { flash('Only tasks that are In Progress can be completed.', 'err'); break; }
        $body = post('body', 2000);
        if ($body !== '') run("INSERT INTO task_comments(task_id,admin_id,author,body,created_at) VALUES(?,?,?,?,?)", [$id, $me['id'], $me['name'] . ($me['title'] ? ' (' . $me['title'] . ')' : ''), $body, now()]);
        run("UPDATE tasks SET status='Completed', completed_at=?, completed_by=?, updated_at=? WHERE id=?", [now(), $me['name'], now(), $id]);
        log_act('task', $id, 'Marked Completed by ' . $me['name']);
        if ($t['student_id']) log_act('student', (int)$t['student_id'], 'Task completed: ' . $t['title'] . ' (' . $me['name'] . ')');
        flash('Task marked as completed. Well done!'); break;
    case 'reopen':
        require_perm('tasks.assign');
        task_assign($id, $t['assigned_to'] ? (int)$t['assigned_to'] : null); flash('Task re-opened.'); break;
    case 'edit':
        require_perm('tasks.assign');
        $title = line(post('title', 160)); if (mb_strlen($title) < 3) { flash('Enter a task title.', 'err'); break; }
        run("UPDATE tasks SET title=?, details=?, updated_at=? WHERE id=?", [$title, post('details', 2000), now(), $id]); log_act('task', $id, 'Task details edited'); flash('Task updated.'); break;
    case 'delete':
        require_perm('tasks.assign');
        run("DELETE FROM tasks WHERE id=?", [$id]); flash('Task deleted.'); redirect($t['student_id'] && can('students.view') ? 'student.php?id=' . (int)$t['student_id'] : 'tasks.php');
    }
    redirect($back);
}

$comments = q("SELECT * FROM task_comments WHERE task_id=? ORDER BY id", [$id]);
$canComment = $mine($t) || can('tasks.viewall');
$late = $t['due_date'] && $t['status'] === 'In Progress' && $t['due_date'] < date('Y-m-d');
admin_head('Task', 'tasks'); ?>
<p><a href="tasks.php" class="muted">← Tasks</a></p>
<div class="grid g21">
<div>
  <div class="card"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start"><h3 style="margin:0;font-size:20px"><?= e($t['title']) ?></h3><?= badge($t['status']) ?></div>
    <?php if ($t['details']): ?><div class="msg" style="margin-top:14px"><?= e($t['details']) ?></div><?php endif; ?>
    <dl class="kv" style="margin-top:18px">
      <dt>Student</dt><dd><?php if ($t['student_id']): ?><?= can('students.view') ? '<a href="student.php?id=' . (int)$t['student_id'] . '" style="color:var(--brand-600)">' . e($t['sname']) . '</a>' : e($t['sname']) ?> <span class="muted small">(<?= e($t['scode']) ?>)</span><br><span class="muted small"><?= e($t['course_title']) ?></span><?php else: ?>—<?php endif; ?></dd>
      <dt>Assigned to</dt><dd><?= $t['aname'] ? e($t['aname']) . ' <span class="muted small">' . e($t['atitle']) . '</span>' : '<span class="muted">Nobody yet</span>' ?></dd>
      <dt>Due</dt><dd style="<?= $late ? 'color:#9B2C20' : '' ?>"><?= $t['due_date'] ? e(dt($t['due_date'])) . ($late ? ' — overdue' : '') : '—' ?></dd>
      <dt>Created</dt><dd><?= e(dt($t['created_at'], 'd M Y, H:i')) ?> by <?= e($t['created_by']) ?></dd>
      <?php if ($t['completed_at']): ?><dt>Completed</dt><dd><?= e(dt($t['completed_at'], 'd M Y, H:i')) ?> by <?= e($t['completed_by']) ?></dd><?php endif; ?></dl>
    <?php if (($mine($t) || is_super()) && $t['status'] === 'In Progress'): ?>
      <form method="post" style="margin-top:20px;border-top:1px solid var(--line);padding-top:18px"><?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?= $id ?>">
      <div class="f"><label>Finished? Add a closing note (optional) and mark this task completed</label><textarea name="body" style="min-height:70px" placeholder="What was done, e.g. Invoice INV-2026-0004 sent to the student."></textarea></div>
      <button class="btn" onclick="return confirm('Mark this task as completed?')">✓ Mark as completed</button></form>
    <?php endif; ?>
  </div>

  <div class="card"><h3>Updates &amp; comments (<?= count($comments) ?>)</h3>
    <?php if (!$comments): ?><p class="muted">No updates yet. <?= $mine($t) ? 'Post an update to let the team know what you are doing.' : '' ?></p><?php endif; ?>
    <ul class="log"><?php foreach ($comments as $c): ?><li><span><b><?= e($c['author']) ?></b> · <?= e(dt($c['created_at'], 'd M Y, H:i')) ?></span><?= nl2br(e($c['body'])) ?></li><?php endforeach; ?></ul>
    <?php if ($canComment): ?><form method="post" style="margin-top:16px"><?= csrf_field() ?><input type="hidden" name="action" value="comment"><input type="hidden" name="id" value="<?= $id ?>">
      <div class="f"><label>Add an update</label><textarea name="body" required placeholder="Write what you are working on or any blockers…"></textarea></div><button class="btn dark">Post comment</button></form><?php endif; ?>
  </div>
</div>
<div>
  <?php if (is_super()): ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="assign"><input type="hidden" name="id" value="<?= $id ?>"><h3>Assign task</h3>
    <div class="f"><label>Staff member</label><select name="assignee"><?= staff_options($t['assigned_to'] ? (int)$t['assigned_to'] : null, 'Not assigned') ?></select></div>
    <div class="f"><label>Due date (optional)</label><input type="date" name="due_date" value="<?= e($t['due_date']) ?>"></div>
    <p class="muted small" style="margin-top:-4px">Choosing a person sets the status to <b>In Progress</b>. Choosing “Not assigned” sets it to <b>Not Assigned</b>.</p>
    <button class="btn">Save assignment</button></form>
  <?php if ($t['status'] === 'Completed'): ?><form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="reopen"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn ghost">Re-open this task</button></form><?php endif; ?>
  <details class="card"><summary>Edit task details</summary><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= $id ?>">
    <div class="f"><label>Title</label><input name="title" value="<?= e($t['title']) ?>" required></div><div class="f"><label>Details</label><textarea name="details"><?= e($t['details']) ?></textarea></div><button class="btn dark sm">Save</button></form></details>
  <form method="post" class="card" data-confirm="Delete this task and its comments?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger sm">Delete task</button></form>
  <?php endif; ?>
  <?php activity_box('task', $id); ?>
</div></div>
<?php admin_foot();
