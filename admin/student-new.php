<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('students.create');
$courses = q("SELECT * FROM courses ORDER BY sort,title");
$v = ['name' => $_GET['name'] ?? '', 'age' => '', 'sex' => '', 'location' => '', 'course_id' => '', 'email' => $_GET['email'] ?? '', 'phone' => $_GET['phone'] ?? ''];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = line(post($k, 120));
    if (mb_strlen($v['name']) < 2) $err = 'Name is required.';
    elseif (!ctype_digit($v['age']) || (int)$v['age'] < 1) $err = 'Enter a valid age.';
    elseif (!in_array($v['sex'], SEXES, true)) $err = 'Select sex.';
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
    elseif ($v['phone'] === '') $err = 'Phone is required.';
    elseif (!q1("SELECT 1 FROM courses WHERE id=?", [(int)$v['course_id']])) $err = 'Select a course.';
    else {
        $r = create_student($v);
        flash('Student created. Student ID: ' . $r['code'] . ' (' . $v['name'] . ', ' . $r['course']['title'] . ')');
        redirect(can('students.view') ? 'student.php?id=' . $r['id'] : 'student-new.php');   // Intake Officers stay on the form to add the next student
    }
}
admin_head('Add student', 'students'); ?>
<?php if (can('students.view')): ?><p><a href="students.php" class="muted">← All students</a></p><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="card" style="max-width:760px"><?= csrf_field() ?><p class="muted">For walk-ins and phone enrolments. A unique student ID is generated automatically.</p>
<div class="f"><label>Course *</label><select name="course_id" required><option value="">Select…</option><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$c['id'] === $v['course_id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
<div class="f"><label>Full name *</label><input name="name" required value="<?= e($v['name']) ?>"></div>
<div class="row3 row"><div class="f"><label>Age *</label><input type="number" name="age" min="1" max="99" required value="<?= e($v['age']) ?>"></div>
<div class="f"><label>Sex *</label><select name="sex" required><option value="">Select</option><?= opts(SEXES, $v['sex']) ?></select></div>
<div class="f"><label>Location *</label><input name="location" required value="<?= e($v['location']) ?>"></div></div>
<div class="row"><div class="f"><label>Email *</label><input type="email" name="email" required value="<?= e($v['email']) ?>"></div><div class="f"><label>Phone *</label><input name="phone" required value="<?= e($v['phone']) ?>"></div></div>
<button class="btn">Create student</button></form>
<?php admin_foot();
