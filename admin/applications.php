<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
$fj = (int)($_GET['job'] ?? 0); $fs = (string)($_GET['status'] ?? ''); $fq = trim((string)($_GET['q'] ?? ''));
$where = []; $p = [];
if ($fj) { $where[] = 'a.job_id=?'; $p[] = $fj; }
if (in_array($fs, APP_STATUSES, true)) { $where[] = 'a.status=?'; $p[] = $fs; }
if ($fq !== '') { $where[] = "(a.name LIKE ? OR a.email LIKE ? OR a.phone LIKE ? OR a.code LIKE ? OR j.title LIKE ?)"; array_push($p, "%$fq%", "%$fq%", "%$fq%", "%$fq%", "%$fq%"); }
$sql = "SELECT a.*, j.title jtitle, j.company jcompany FROM applications a JOIN jobs j ON j.id=a.job_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY a.id DESC";
if (isset($_GET['export'])) {
    $all = q($sql, $p);
    log_act('export', 0, 'Exported ' . count($all) . ' application(s) to CSV');
    csv_download('applications-' . date('Y-m-d') . '.csv', ['Reference', 'Job', 'Company', 'Applicant name', 'Email', 'Phone', 'LinkedIn', 'Status', 'Applied', 'Last updated', 'CV file', 'Role question answer', 'Cover letter', 'Info requested', 'Decision note', 'Internal note', 'Reviewed by', 'Portfolio', 'Location', 'Expected pay', 'Available from', 'Interview'],
        array_map(fn($a) => [$a['code'], $a['jtitle'], $a['jcompany'], $a['name'], $a['email'], $a['phone'], $a['linkedin'], $a['status'], $a['created_at'], $a['updated_at'], $a['cv_name'], $a['answer'], $a['cover_letter'], $a['info_request'], $a['decision_note'], $a['admin_note'], $a['reviewed_by'], $a['portfolio'], $a['location'], $a['expected_pay'], $a['available_from'], $a['interview_at']], $all));
}
$rows = q($sql . " LIMIT 300", $p);
$cj = $fj ? ' AND job_id=' . $fj : '';
$counts = array_column(q("SELECT status, COUNT(*) n FROM applications WHERE 1=1$cj GROUP BY status"), 'n', 'status');
$jobs = q("SELECT id,title FROM jobs ORDER BY sort,id");
$qs = fn(array $x) => http_build_query(array_filter(array_merge(['job' => $fj, 'status' => $fs, 'q' => $fq], $x)));
admin_head('Job applications', 'applications'); ?>
<div class="tabs"><a href="?<?= $qs(['status' => '']) ?>" class="<?= $fs === '' ? 'on' : '' ?>">All<i><?= array_sum($counts) ?></i></a>
<?php foreach (APP_STATUSES as $s): ?><a href="?<?= $qs(['status' => $s]) ?>" class="<?= $fs === $s ? 'on' : '' ?>"><?= e($s) ?><i><?= (int)($counts[$s] ?? 0) ?></i></a><?php endforeach; ?></div>
<form class="bar" method="get">
  <select name="job" onchange="this.form.submit()"><option value="">All jobs</option><?php foreach ($jobs as $j): ?><option value="<?= $j['id'] ?>" <?= $fj === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['title']) ?></option><?php endforeach; ?></select>
  <?php if ($fs): ?><input type="hidden" name="status" value="<?= e($fs) ?>"><?php endif; ?>
  <input class="grow" name="q" placeholder="Search name, email, phone, reference or job…" value="<?= e($fq) ?>"><button class="btn dark">Search</button>
  <a class="btn ghost" href="?<?= $qs(['export' => 1]) ?>">⬇ Export CSV<?= ($fj || $fs || $fq) ? ' (filtered)' : '' ?></a></form>
<div class="card"><div class="tw"><table><thead><tr><th>Applied</th><th>Applicant</th><th>Job</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
 <td class="muted small"><?= e(dt($r['created_at'])) ?><br><?= e($r['code']) ?></td>
 <td><a href="application.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><span class="muted small"><?= e($r['email']) ?> · <?= e($r['phone']) ?></span></td>
 <td><?= e($r['jtitle']) ?><br><span class="muted small"><?= e($r['jcompany']) ?></span></td>
 <td><?= badge($r['status']) ?><?= (int)$r['edit_unlocked'] ? '<br><span class="muted small">editing unlocked</span>' : '' ?></td>
 <td class="num"><a class="btn sm" href="application.php?id=<?= $r['id'] ?>">Review</a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No applications found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
