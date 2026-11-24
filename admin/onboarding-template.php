<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.settings');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = post('action', 12); $id = (int)post('id');
    if ($act === 'save' || $act === 'add') {
        $title = line(post('title', 160));
        if ($title === '') { flash('Give the task a title.', 'err'); redirect('onboarding-template.php'); }
        $owner = in_array(post('owner', 10), ONB_OWNERS, true) ? post('owner', 10) : 'HR';
        $cat = in_array(post('category', 30), ONB_CATEGORIES, true) ? post('category', 30) : 'Orientation';
        $days = max(-60, min(365, (int)post('due_days', 5))); $nf = post('needs_file', 1) === '1' && $owner === 'Employee' ? 1 : 0;
        if ($act === 'add') { run("INSERT INTO onboarding_template(title,description,category,owner,due_days,needs_file,sort) VALUES(?,?,?,?,?,?,?)", [$title, post('description', 400), $cat, $owner, $days, $nf, 1 + (int)qv("SELECT COALESCE(MAX(sort),0) FROM onboarding_template")]); flash('Task added to the checklist.'); }
        else { run("UPDATE onboarding_template SET title=?,description=?,category=?,owner=?,due_days=?,needs_file=?,sort=?,active=? WHERE id=?", [$title, post('description', 400), $cat, $owner, $days, $nf, (int)post('sort', 4), post('active', 1) === '1' ? 1 : 0, $id]); flash('Task saved.'); }
        log_act('careers', 0, 'Onboarding checklist template changed: ' . $title);
    } elseif ($act === 'delete') { run("DELETE FROM onboarding_template WHERE id=?", [$id]); flash('Task removed from the template.'); log_act('careers', 0, 'Onboarding template task removed'); }
    redirect('onboarding-template.php');
}
$rows = q("SELECT * FROM onboarding_template ORDER BY sort,id");
admin_head('Onboarding checklist', 'onbtpl'); ?>
<div class="card"><h3>The checklist every new hire gets</h3>
  <p class="muted" style="margin-top:-8px">When an applicant is marked <b>Hired</b>, these tasks are copied to their onboarding record. Changes here affect <b>future hires only</b>. Tasks owned by <b>Employee</b> appear in the new hire’s portal, where they can tick them off or upload documents. “Due” is in days from the start date (negative = before day one).</p>
  <div class="tw"><table class="tpl"><thead><tr><th>#</th><th>Task</th><th>Category</th><th>Owner</th><th>Due</th><th>File</th><th>On</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $f = 'f' . $r['id']; ?><tr>
    <td><input form="<?= $f ?>" type="number" name="sort" value="<?= (int)$r['sort'] ?>" style="width:62px" aria-label="Order"></td>
    <td style="min-width:260px"><input form="<?= $f ?>" name="title" value="<?= e($r['title']) ?>" maxlength="160" aria-label="Task title"><input form="<?= $f ?>" name="description" value="<?= e($r['description']) ?>" maxlength="400" placeholder="Details (optional)" style="margin-top:6px" aria-label="Details"></td>
    <td><select form="<?= $f ?>" name="category" aria-label="Category"><?= opts(ONB_CATEGORIES, (string)$r['category']) ?></select></td>
    <td><select form="<?= $f ?>" name="owner" aria-label="Owner"><?= opts(ONB_OWNERS, (string)$r['owner']) ?></select></td>
    <td><input form="<?= $f ?>" type="number" name="due_days" value="<?= (int)$r['due_days'] ?>" style="width:74px" aria-label="Due days"></td>
    <td><input form="<?= $f ?>" type="checkbox" name="needs_file" value="1" <?= (int)$r['needs_file'] ? 'checked' : '' ?> aria-label="Needs file" style="width:auto"></td>
    <td><input form="<?= $f ?>" type="checkbox" name="active" value="1" <?= (int)$r['active'] ? 'checked' : '' ?> aria-label="Active" style="width:auto"></td>
    <td class="num"><form id="<?= $f ?>" method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm" name="action" value="save">Save</button></form>
      <form method="post" style="display:inline" data-confirm="Remove this task from the template?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger" name="action" value="delete">×</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div></div>
<div class="card"><h3>Add a task</h3><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="row"><div class="f"><label for="t">Task</label><input id="t" name="title" maxlength="160" required></div><div class="f"><label for="d">Details (optional)</label><input id="d" name="description" maxlength="400"></div></div>
  <div class="row row3"><div class="f"><label for="c">Category</label><select id="c" name="category"><?= opts(ONB_CATEGORIES) ?></select></div><div class="f"><label for="o">Owner</label><select id="o" name="owner"><?= opts(ONB_OWNERS, 'HR') ?></select></div><div class="f"><label for="dd">Due (days from start)</label><input id="dd" type="number" name="due_days" value="0"></div></div>
  <label class="check-line"><input type="checkbox" name="needs_file" value="1"> Needs a file upload (Employee tasks only)</label><button class="btn">Add to checklist</button></form></div>
<?php admin_foot();
