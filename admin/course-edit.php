<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('courses.edit');
$id = (int)($_GET['id'] ?? 0);
$c = $id ? q1("SELECT * FROM courses WHERE id=?", [$id]) : null;
if ($id && !$c) { flash('Course not found.', 'err'); redirect('courses.php'); }
$icons = ['code' => 'Code', 'cloud' => 'Cloud', 'lock' => 'Security', 'refresh' => 'DevOps / cycle', 'pen' => 'Design', 'monitor' => 'Monitor', 'server' => 'Server', 'bulb' => 'Idea', 'grad' => 'Graduation', 'shield' => 'Shield', 'users' => 'People', 'layers' => 'Layers', 'spark' => 'Sparkle', 'target' => 'Target'];
$f = $c ?: ['title' => '', 'icon' => 'code', 'summary' => '', 'description' => '', 'outline' => '', 'requirements' => '', 'duration' => '', 'mode' => 'Virtual or In-person', 'level' => '', 'fee' => '', 'start_info' => '', 'active' => 1, 'sort' => (int)qv("SELECT COALESCE(MAX(sort),0)+1 FROM courses")];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete' && $c) { require_perm('courses.delete');
        if ((int)qv("SELECT COUNT(*) FROM students WHERE course_id=?", [$id])) { flash('This course has students, so it cannot be deleted. Hide it instead.', 'err'); redirect("course-edit.php?id=$id"); }
        run("DELETE FROM courses WHERE id=?", [$id]); flash('Course deleted.'); redirect('courses.php');
    }
    $f = ['title' => line(post('title', 120)), 'icon' => array_key_exists(post('icon'), $icons) ? post('icon') : 'code', 'summary' => post('summary', 300), 'description' => post('description', 6000),
          'outline' => post('outline', 4000), 'requirements' => post('requirements', 2000), 'duration' => line(post('duration', 60)), 'mode' => line(post('mode', 60)), 'level' => line(post('level', 60)),
          'fee' => max(0, (float)str_replace(',', '', post('fee', 20))), 'start_info' => line(post('start_info', 80)), 'active' => isset($_POST['active']) ? 1 : 0, 'sort' => (int)post('sort', 5)];
    if (mb_strlen($f['title']) < 3) $err = 'Course title is required.';
    elseif ($f['summary'] === '') $err = 'Add a short summary for the course card.';
    else {
        if ($c) {
            run("UPDATE courses SET title=?,icon=?,summary=?,description=?,outline=?,requirements=?,duration=?,mode=?,level=?,fee=?,start_info=?,active=?,sort=? WHERE id=?",
                [$f['title'], $f['icon'], $f['summary'], $f['description'], $f['outline'], $f['requirements'], $f['duration'], $f['mode'], $f['level'], $f['fee'], $f['start_info'], $f['active'], $f['sort'], $id]);
            flash('Course saved.');
        } else {
            $slug = slugify($f['title']); $base = $slug; $i = 2;
            while (q1("SELECT 1 FROM courses WHERE slug=?", [$slug])) $slug = $base . '-' . $i++;
            run("INSERT INTO courses(slug,title,icon,summary,description,outline,requirements,duration,mode,level,fee,start_info,active,sort,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$slug, $f['title'], $f['icon'], $f['summary'], $f['description'], $f['outline'], $f['requirements'], $f['duration'], $f['mode'], $f['level'], $f['fee'], $f['start_info'], $f['active'], $f['sort'], now()]);
            flash('Course added. It is now on the website' . ($f['active'] ? '.' : ' (hidden).'));
        }
        redirect('courses.php');
    }
}
admin_head($c ? 'Edit course' : 'Add course', 'courses'); ?>
<p><a href="courses.php" class="muted">← All courses</a></p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
  <div class="row"><div class="f"><label>Course title *</label><input name="title" required value="<?= e($f['title']) ?>"></div>
  <div class="f"><label>Icon</label><select name="icon"><?php foreach ($icons as $k => $n): ?><option value="<?= $k ?>" <?= $f['icon'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></div></div>
  <div class="f"><label>Short summary * (shown on the course card)</label><textarea name="summary" style="min-height:70px" required><?= e($f['summary']) ?></textarea></div>
  <div class="f"><label>Full description (leave a blank line between paragraphs)</label><textarea name="description" style="min-height:140px"><?= e($f['description']) ?></textarea></div>
  <div class="row"><div class="f"><label>What students will learn (one topic per line)</label><textarea name="outline" style="min-height:170px"><?= e($f['outline']) ?></textarea></div>
  <div class="f"><label>Requirements (one per line)</label><textarea name="requirements" style="min-height:170px"><?= e($f['requirements']) ?></textarea></div></div>
  <div class="row3 row"><div class="f"><label>Duration</label><input name="duration" placeholder="e.g. 12 weeks" value="<?= e($f['duration']) ?>"></div>
  <div class="f"><label>Delivery mode</label><input name="mode" placeholder="Virtual or In-person" value="<?= e($f['mode']) ?>"></div>
  <div class="f"><label>Level</label><input name="level" placeholder="Beginner" value="<?= e($f['level']) ?>"></div></div>
  <div class="row3 row"><div class="f"><label>Course fee (<?= e(setting('currency')) ?>) — used for invoices</label><input name="fee" inputmode="decimal" value="<?= e($f['fee'] ?: '') ?>" placeholder="0 = “Contact us”"></div>
  <div class="f"><label>Next start / cohort info</label><input name="start_info" placeholder="e.g. January 2027" value="<?= e($f['start_info']) ?>"></div>
  <div class="f"><label>Display order</label><input type="number" name="sort" value="<?= (int)$f['sort'] ?>"></div></div>
  <div class="f"><label style="display:flex;gap:10px;align-items:center;font-weight:600"><input type="checkbox" name="active" style="width:auto" <?= $f['active'] ? 'checked' : '' ?>> Show this course on the website</label></div>
  <p class="muted small">Changing the fee does not alter existing students' fees; adjust those on each student's page.</p>
  <button class="btn"><?= $c ? 'Save course' : 'Add course' ?></button> <a class="btn ghost" href="courses.php">Cancel</a>
</form>
<?php if ($c && can('courses.delete')): ?><form method="post" data-confirm="Delete this course permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn danger sm">Delete course</button></form><?php endif; ?>
<?php admin_foot();
