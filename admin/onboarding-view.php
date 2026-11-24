<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$o = q1("SELECT o.*, a.name, a.email, a.phone, a.id aid, a.code, j.title jtitle, j.company jcompany FROM onboardings o JOIN applications a ON a.id=o.application_id JOIN jobs j ON j.id=a.job_id WHERE o.id=?", [$id]);
if (!$o) { flash('Onboarding record not found.', 'err'); redirect('onboarding.php'); }
$me = admin_user()['name'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_perm('careers.manage');
    $act = post('action', 20); $back = 'onboarding-view.php?id=' . $id;
    switch ($act) {
        case 'toggle':
            $t = q1("SELECT * FROM onboarding_tasks WHERE id=? AND onboarding_id=?", [(int)post('task'), $id]);
            if ($t) {
                $d = (int)$t['done'] ? 0 : 1;
                run("UPDATE onboarding_tasks SET done=?, done_at=?, done_by=? WHERE id=?", [$d, $d ? now() : null, $d ? $me : null, $t['id']]);
                log_act('application', (int)$o['aid'], 'Onboarding task ' . ($d ? 'done' : 'reopened') . ': ' . $t['title']);
                if ($d && $t['owner'] === 'Employee') app_event((int)$o['aid'], 'onboarding', 'Onboarding task completed: ' . $t['title'], $me);
            }
            redirect($back . '#t' . (int)post('task'));
        case 'add':
            $title = line(post('title', 160));
            if ($title === '') { flash('Give the task a title.', 'err'); redirect($back); }
            $owner = in_array(post('owner', 10), ONB_OWNERS, true) ? post('owner', 10) : 'HR';
            $cat = in_array(post('category', 30), ONB_CATEGORIES, true) ? post('category', 30) : 'Orientation';
            $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('due', 10)) ? post('due', 10) : null;
            run("INSERT INTO onboarding_tasks(onboarding_id,title,description,category,owner,due_date,needs_file,sort) VALUES(?,?,?,?,?,?,?,?)",
                [$id, $title, post('description', 400), $cat, $owner, $due, post('needs_file', 1) === '1' && $owner === 'Employee' ? 1 : 0, 900 + (int)qv("SELECT COUNT(*) FROM onboarding_tasks WHERE onboarding_id=?", [$id])]);
            log_act('application', (int)$o['aid'], 'Onboarding task added: ' . $title); flash('Task added.'); redirect($back);
        case 'remove':
            run("DELETE FROM onboarding_tasks WHERE id=? AND onboarding_id=?", [(int)post('task'), $id]); flash('Task removed.'); redirect($back);
        case 'details':
            $sd = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('start_date', 10)) ? post('start_date', 10) : null;
            if ($sd && $sd !== $o['start_date']) {   // re-date tasks that still follow the template
                $tpl = q("SELECT title,due_days FROM onboarding_template"); $days = array_column($tpl, 'due_days', 'title');
                foreach (q("SELECT id,title FROM onboarding_tasks WHERE onboarding_id=? AND done=0", [$id]) as $t)
                    if (isset($days[$t['title']])) run("UPDATE onboarding_tasks SET due_date=? WHERE id=?", [date('Y-m-d', strtotime($sd . ' ' . ((int)$days[$t['title']] >= 0 ? '+' : '') . (int)$days[$t['title']] . ' days')), $t['id']]);
            }
            run("UPDATE onboardings SET start_date=?, manager=?, notes=? WHERE id=?", [$sd, post('manager', 120), post('notes', 2000), $id]);
            log_act('application', (int)$o['aid'], 'Onboarding details updated'); flash('Onboarding details saved.'); redirect($back);
        case 'complete':
            [$d, $t] = hr_onb_progress($id);
            run("UPDATE onboardings SET status='Completed', completed_at=? WHERE id=?", [now(), $id]);
            app_event((int)$o['aid'], 'onboarding', 'Your onboarding is complete. Welcome to the team!', $me);
            log_act('application', (int)$o['aid'], 'Onboarding marked complete (' . $d . '/' . $t . ' tasks done)'); flash('Onboarding marked as complete.'); redirect($back);
        case 'reopen':
            run("UPDATE onboardings SET status='In progress', completed_at=NULL WHERE id=?", [$id]); flash('Onboarding reopened.'); redirect($back);
    }
    redirect($back);
}
$tasks = q("SELECT * FROM onboarding_tasks WHERE onboarding_id=? ORDER BY sort,id", [$id]);
[$done, $total] = hr_onb_progress($id);
$files = array_column(q("SELECT id,original FROM application_files WHERE application_id=?", [$o['aid']]), 'original', 'id');
$can = can('careers.manage'); $today = date('Y-m-d');
admin_head('Onboarding: ' . $o['name'], 'onboarding'); ?>
<p><a href="onboarding.php" class="muted">← New hires</a> · <a href="application.php?id=<?= (int)$o['aid'] ?>">View application <?= e($o['code']) ?></a></p>
<div class="grid g21">
 <div>
  <div class="card"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><h3 style="margin:0"><?= e($o['name']) ?></h3><span class="muted small"><?= e($o['jtitle']) ?> · <?= e($o['jcompany']) ?><?= $o['start_date'] ? ' · starts ' . e(dt($o['start_date'], 'D j M Y')) : '' ?></span></div><div><?= badge($o['status']) ?></div></div>
    <div style="margin-top:14px"><?= hr_progress_bar($done, $total) ?></div></div>
  <?php foreach (ONB_CATEGORIES as $cat): $ct = array_filter($tasks, fn($t) => $t['category'] === $cat); if (!$ct) continue; ?>
  <div class="card"><h3><?= e($cat) ?></h3>
   <?php foreach ($ct as $t): $late = !(int)$t['done'] && $t['due_date'] && $t['due_date'] < $today; ?>
    <div class="onb-row <?= (int)$t['done'] ? 'done' : '' ?>" id="t<?= $t['id'] ?>">
      <?php if ($can): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="task" value="<?= $t['id'] ?>"><button class="chk" aria-label="<?= (int)$t['done'] ? 'Reopen' : 'Mark done' ?>: <?= e($t['title']) ?>"><?= (int)$t['done'] ? '✓' : '' ?></button></form>
      <?php else: ?><span class="chk"><?= (int)$t['done'] ? '✓' : '' ?></span><?php endif; ?>
      <div class="ob-main"><b><?= e($t['title']) ?></b><?php if ($t['description']): ?><small><?= e($t['description']) ?></small><?php endif; ?>
        <small class="ob-meta"><span class="own own-<?= strtolower($t['owner']) ?>"><?= e($t['owner']) ?></span><?= $t['due_date'] ? ' · due ' . e(dt($t['due_date'])) : '' ?><?= $late ? ' <span class="b b-closed">overdue</span>' : '' ?><?= (int)$t['done'] ? ' · done ' . e(dt($t['done_at'])) . ($t['done_by'] ? ' by ' . e($t['done_by']) : '') : '' ?>
          <?php if ($t['file_id'] && isset($files[$t['file_id']])): ?> · <a href="application-file.php?a=<?= (int)$o['aid'] ?>&amp;f=<?= (int)$t['file_id'] ?>">⬇ <?= e($files[$t['file_id']]) ?></a><?php elseif ((int)$t['needs_file'] && !(int)$t['done']): ?> · waiting for upload<?php endif; ?></small></div>
      <?php if ($can): ?><form method="post" data-confirm="Remove this task from the checklist?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="task" value="<?= $t['id'] ?>"><button class="x" aria-label="Remove task">×</button></form><?php endif; ?>
    </div>
   <?php endforeach; ?></div>
  <?php endforeach; ?>
  <?php activity_box('application', (int)$o['aid']); ?>
 </div>
 <div>
  <?php if ($can): ?>
  <div class="card"><h3>Details</h3><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="details">
    <div class="f"><label for="start_date">Start date</label><input id="start_date" type="date" name="start_date" value="<?= e($o['start_date']) ?>"></div>
    <div class="f"><label for="manager">Reports to / buddy</label><input id="manager" name="manager" value="<?= e($o['manager']) ?>" maxlength="120"></div>
    <div class="f"><label for="notes">Internal notes</label><textarea id="notes" name="notes"><?= e($o['notes']) ?></textarea></div>
    <button class="btn dark sm">Save details</button></form></div>
  <div class="card"><h3>Add a task</h3><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="add">
    <div class="f"><label for="nt">Task</label><input id="nt" name="title" maxlength="160" required></div>
    <div class="f"><label for="nd">Details (optional)</label><input id="nd" name="description" maxlength="400"></div>
    <div class="row"><div class="f"><label for="nc">Category</label><select id="nc" name="category"><?= opts(ONB_CATEGORIES) ?></select></div><div class="f"><label for="no">Who does it</label><select id="no" name="owner"><?= opts(ONB_OWNERS, 'HR') ?></select></div></div>
    <div class="row"><div class="f"><label for="ndue">Due</label><input id="ndue" type="date" name="due"></div><div class="f"><label>&nbsp;</label><label class="check-line" style="margin:10px 0 0"><input type="checkbox" name="needs_file" value="1"> Needs a file upload</label></div></div>
    <button class="btn sm">Add task</button></form></div>
  <div class="card"><h3>Finish</h3>
    <?php if ($o['status'] === 'In progress'): ?><p class="muted small" style="margin-top:-6px"><?= $done ?> of <?= $total ?> tasks done. You can complete onboarding at any time.</p>
      <form method="post" data-confirm="Mark this onboarding as complete?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="complete"><button class="btn good">✓ Mark onboarding complete</button></form>
    <?php else: ?><p class="muted small" style="margin-top:-6px">Completed <?= e(dt($o['completed_at'])) ?>.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="reopen"><button class="btn ghost sm">Reopen</button></form><?php endif; ?></div>
  <?php else: ?><div class="card"><p class="muted">Your access level can view onboarding but not change it.</p></div><?php endif; ?>
 </div>
</div>
<?php admin_foot();
