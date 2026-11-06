<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('courses.view');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_perm('courses.edit');
    $id = (int)post('id');
    if (post('action') === 'toggle' && ($c = q1("SELECT * FROM courses WHERE id=?", [$id]))) {
        run("UPDATE courses SET active=? WHERE id=?", [$c['active'] ? 0 : 1, $id]);
        flash($c['title'] . ($c['active'] ? ' hidden from the website.' : ' is now live on the website.'));
    }
    redirect('courses.php');
}
$rows = q("SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.course_id=c.id) n FROM courses c ORDER BY sort, title");
admin_head('Courses', 'courses'); ?>
<div class="bar"><span class="grow muted">Courses shown on the website's Training page. Add, edit or hide courses here.</span><a class="btn" href="course-edit.php">+ Add course</a></div>
<div class="card"><div class="tw"><table><thead><tr><th>Order</th><th>Course</th><th>Duration</th><th>Fee</th><th>Students</th><th>Website</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $c): ?><tr><td class="muted"><?= (int)$c['sort'] ?></td>
<td><b><?= e($c['title']) ?></b><br><span class="muted small">/course.php?c=<?= e($c['slug']) ?></span></td>
<td><?= e($c['duration'] ?: '—') ?></td><td><?= $c['fee'] > 0 ? e(money($c['fee'])) : '<span class="muted">Not set</span>' ?></td>
<td><a href="students.php?course=<?= $c['id'] ?>"><?= (int)$c['n'] ?></a></td>
<td><?= $c['active'] ? badge('Enrolled') : badge('Closed') ?> <span class="small muted"><?= $c['active'] ? 'Live' : 'Hidden' ?></span></td>
<td class="num"><div class="actions"><a class="btn sm ghost" href="course-edit.php?id=<?= $c['id'] ?>">Edit</a>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn sm ghost"><?= $c['active'] ? 'Hide' : 'Publish' ?></button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<?php admin_foot();
