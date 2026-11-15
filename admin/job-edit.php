<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
$id = (int)($_GET['id'] ?? 0);
$j = $id ? q1("SELECT * FROM jobs WHERE id=?", [$id]) : null;
if ($id && !$j) { flash('Job not found.', 'err'); redirect('jobs.php'); }
$f = $j ?: ['title' => '', 'company' => 'Empre Technologies', 'department' => '', 'type' => 'Full-time', 'location' => '', 'work_mode' => '', 'reports_to' => '', 'pay' => '', 'icon' => 'users', 'summary' => '', 'about_company' => '',
            'role_desc' => '', 'responsibilities' => '', 'requirements' => '', 'nice_to_have' => '', 'success' => '', 'benefits' => '', 'extra_question' => '', 'extra_required' => 0, 'closing_date' => '', 'start_date' => '', 'status' => 'Draft', 'sort' => (int)qv("SELECT COALESCE(MAX(sort),0)+1 FROM jobs")];
$nApps = $j ? (int)qv("SELECT COUNT(*) FROM applications WHERE job_id=?", [$id]) : 0;
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete') {
        require_perm('careers.delete');
        if ($j) {
            foreach (q("SELECT cv_file FROM applications WHERE job_id=?", [$id]) as $a) delete_upload($a['cv_file']);
            foreach (q("SELECT f.file FROM application_files f JOIN applications a ON a.id=f.application_id WHERE a.job_id=?", [$id]) as $x) delete_upload($x['file']);
            run("DELETE FROM application_events WHERE application_id IN (SELECT id FROM applications WHERE job_id=?)", [$id]);
            run("DELETE FROM application_files WHERE application_id IN (SELECT id FROM applications WHERE job_id=?)", [$id]);
            run("DELETE FROM applications WHERE job_id=?", [$id]); run("DELETE FROM jobs WHERE id=?", [$id]);
            log_act('job', $id, 'Deleted job "' . $j['title'] . '" with ' . $nApps . ' application(s)');
            flash('Job "' . $j['title'] . '" was deleted' . ($nApps ? ' together with its ' . $nApps . ' application(s) and uploaded files.' : '.'));
        }
        redirect('jobs.php');
    }
    require_perm('careers.manage');
    $cd = post('closing_date', 10);
    $f = ['title' => line(post('title', 140)), 'company' => line(post('company', 100)), 'department' => line(post('department', 80)), 'type' => line(post('type', 80)), 'location' => line(post('location', 120)),
          'work_mode' => line(post('work_mode', 60)), 'reports_to' => line(post('reports_to', 100)), 'pay' => line(post('pay', 160)), 'icon' => svc_icon(post('icon')), 'summary' => post('summary', 300),
          'about_company' => post('about_company', 3000), 'role_desc' => post('role_desc', 3000), 'responsibilities' => post('responsibilities', 5000), 'requirements' => post('requirements', 5000),
          'nice_to_have' => post('nice_to_have', 2000), 'success' => post('success', 2000), 'benefits' => post('benefits', 3000), 'extra_question' => line(post('extra_question', 300)),
          'extra_required' => isset($_POST['extra_required']) ? 1 : 0, 'closing_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $cd) ? $cd : '', 'start_date' => line(post('start_date', 60)),
          'status' => in_array(post('status'), JOB_STATUSES, true) ? post('status') : 'Draft', 'sort' => (int)post('sort', 5)];
    if (mb_strlen($f['title']) < 3) $err = 'Job title is required.';
    elseif ($f['company'] === '') $err = 'Company is required.';
    elseif ($f['summary'] === '') $err = 'Add a one-line summary for the listing.';
    else {
        $cdv = $f['closing_date'] !== '' ? $f['closing_date'] : null;
        $cols = ['title', 'company', 'department', 'type', 'location', 'work_mode', 'reports_to', 'pay', 'icon', 'summary', 'about_company', 'role_desc', 'responsibilities', 'requirements', 'nice_to_have', 'success', 'benefits', 'extra_question', 'extra_required', 'start_date', 'status', 'sort'];
        $vals = array_map(fn($c) => $f[$c], $cols);
        if ($j) {
            run("UPDATE jobs SET " . implode('=?,', $cols) . "=?, closing_date=?, updated_at=? WHERE id=?", array_merge($vals, [$cdv, now(), $id]));
            log_act('job', $id, 'Edited job'); flash('Job saved.');
        } else {
            $nid = run("INSERT INTO jobs(slug," . implode(',', $cols) . ",closing_date,created_at,updated_at) VALUES(?," . implode(',', array_fill(0, count($cols), '?')) . ",?,?,?)",
                array_merge([unique_slug('jobs', $f['title'])], $vals, [$cdv, now(), now()]));
            log_act('job', $nid, 'Added job'); flash('Job added' . ($f['status'] === 'Open' ? ' and now live on the Careers page.' : ' as ' . $f['status'] . '.'));
        }
        redirect('jobs.php');
    }
}
$ro = !can('careers.manage') ? 'readonly disabled' : '';
admin_head($j ? 'Edit job' : 'Add job', 'jobs'); ?>
<p><a href="jobs.php" class="muted">← All jobs</a><?php if ($j && $j['status'] !== 'Draft'): ?> · <a href="../job.php?j=<?= e($j['slug']) ?>" target="_blank">View on website ↗</a><?php endif; ?><?php if ($j): ?> · <a href="applications.php?job=<?= $id ?>"><?= $nApps ?> application(s)</a><?php endif; ?></p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
  <h3>Basics</h3>
  <div class="row"><div class="f"><label>Job title *</label><input name="title" required value="<?= e($f['title']) ?>" <?= $ro ?>></div><div class="f"><label>Company / product hiring *</label><input name="company" value="<?= e($f['company']) ?>" <?= $ro ?>></div></div>
  <div class="row3 row"><div class="f"><label>Team / department</label><input name="department" value="<?= e($f['department']) ?>" placeholder="e.g. Sales" <?= $ro ?>></div>
    <div class="f"><label>Employment type</label><input name="type" list="jt" value="<?= e($f['type']) ?>" <?= $ro ?>><datalist id="jt"><?php foreach (JOB_TYPES as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?></datalist></div>
    <div class="f"><label>Reports to</label><input name="reports_to" value="<?= e($f['reports_to']) ?>" <?= $ro ?>></div></div>
  <div class="row3 row"><div class="f"><label>Location</label><input name="location" value="<?= e($f['location']) ?>" placeholder="e.g. Lagos, Nigeria" <?= $ro ?>></div>
    <div class="f"><label>Work mode</label><input name="work_mode" value="<?= e($f['work_mode']) ?>" placeholder="On-site / Remote / Hybrid" <?= $ro ?>></div>
    <div class="f"><label>Pay (leave empty to hide)</label><input name="pay" value="<?= e($f['pay']) ?>" <?= $ro ?>></div></div>
  <div class="row3 row"><div class="f"><label>Closing date (optional)</label><input type="date" name="closing_date" value="<?= e((string)$f['closing_date']) ?>" <?= $ro ?>></div>
    <div class="f"><label>Start date (optional, text)</label><input name="start_date" value="<?= e($f['start_date']) ?>" placeholder="e.g. 1 February 2027" <?= $ro ?>></div>
    <div class="f"><label>Icon</label><select name="icon" <?= $ro ?>><?php foreach (SERVICE_ICONS as $k => $n): ?><option value="<?= $k ?>" <?= $f['icon'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></div></div>
  <div class="row"><div class="f"><label>Status *</label><select name="status" <?= $ro ?>><?php foreach (JOB_STATUSES as $s): ?><option <?= $f['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select><small class="muted">Draft = hidden. Open = visible and accepting applications. Closed = visible but not accepting.</small></div>
    <div class="f"><label>Display order (smaller = first)</label><input type="number" name="sort" value="<?= (int)$f['sort'] ?>" <?= $ro ?>></div></div>
  <h3 style="margin-top:10px">Description</h3>
  <div class="f"><label>One-line summary * (shown on the listing)</label><textarea name="summary" style="min-height:64px" <?= $ro ?>><?= e($f['summary']) ?></textarea></div>
  <div class="f"><label>The role (paragraph)</label><textarea name="role_desc" style="min-height:110px" <?= $ro ?>><?= e($f['role_desc']) ?></textarea></div>
  <div class="row"><div class="f"><label>What you will do (one per line)</label><textarea name="responsibilities" style="min-height:190px" <?= $ro ?>><?= e($f['responsibilities']) ?></textarea></div>
    <div class="f"><label>What we are looking for (one per line)</label><textarea name="requirements" style="min-height:190px" <?= $ro ?>><?= e($f['requirements']) ?></textarea></div></div>
  <div class="row"><div class="f"><label>Nice to have (one per line)</label><textarea name="nice_to_have" style="min-height:110px" <?= $ro ?>><?= e($f['nice_to_have']) ?></textarea></div>
    <div class="f"><label>How success looks (one per line, format “First 30 days: …”)</label><textarea name="success" style="min-height:110px" <?= $ro ?>><?= e($f['success']) ?></textarea></div></div>
  <div class="row"><div class="f"><label>What we offer (one per line)</label><textarea name="benefits" style="min-height:120px" <?= $ro ?>><?= e($f['benefits']) ?></textarea></div>
    <div class="f"><label>About the company (paragraphs, blank line between)</label><textarea name="about_company" style="min-height:120px" <?= $ro ?>><?= e($f['about_company']) ?></textarea></div></div>
  <h3 style="margin-top:10px">Role-specific question on the application form</h3>
  <div class="row"><div class="f"><label>Question (optional)</label><input name="extra_question" value="<?= e($f['extra_question']) ?>" placeholder="e.g. Share one example of a deal you closed." <?= $ro ?>></div>
    <div class="f"><label style="margin-top:26px;display:flex;gap:10px;align-items:center;font-weight:600"><input type="checkbox" name="extra_required" style="width:auto" <?= $f['extra_required'] ? 'checked' : '' ?> <?= $ro ?>> Applicants must answer it</label></div></div>
  <?php if (can('careers.manage')): ?><button class="btn"><?= $j ? 'Save job' : 'Add job' ?></button> <a class="btn ghost" href="jobs.php">Cancel</a><?php endif; ?>
</form>
<?php if ($j && can('careers.delete')): ?><form method="post" data-confirm="Delete this job<?= $nApps ? ' AND its ' . $nApps . ' application(s) with all uploaded CVs' : '' ?> permanently? This cannot be undone. (Close the job instead if you only want to stop applications.)"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn danger sm">Delete job<?= $nApps ? ' and ' . $nApps . ' application(s)' : '' ?></button></form><?php endif; ?>
<?php admin_foot();
