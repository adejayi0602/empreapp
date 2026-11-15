<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_perm('careers.manage');
    $id = (int)post('id'); $j = q1("SELECT * FROM jobs WHERE id=?", [$id]);
    if ($j && post('action') === 'set_status' && in_array(post('status'), JOB_STATUSES, true)) {
        run("UPDATE jobs SET status=?, updated_at=? WHERE id=?", [post('status'), now(), $id]);
        log_act('job', $id, 'Job status: ' . $j['status'] . ' → ' . post('status')); flash($j['title'] . ' is now ' . post('status') . '.');
    }
    redirect('jobs.php');
}
$rows = q("SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id=j.id) n_all,
           (SELECT COUNT(*) FROM applications a WHERE a.job_id=j.id AND a.status='Received') n_new FROM jobs j ORDER BY j.sort, j.id");
if (isset($_GET['export'])) {
    csv_download('jobs-' . date('Y-m-d') . '.csv', ['Title', 'Company', 'Team', 'Type', 'Location', 'Work mode', 'Reports to', 'Pay', 'Status (public)', 'Closing date', 'Start date', 'Applications', 'Created', 'Link'],
        array_map(fn($r) => [$r['title'], $r['company'], $r['department'], $r['type'], $r['location'], $r['work_mode'], $r['reports_to'], $r['pay'], job_state($r), $r['closing_date'], $r['start_date'], $r['n_all'], $r['created_at'], site_url('job.php?j=' . $r['slug'])], $rows));
}
$open = count(array_filter($rows, 'job_is_open'));
admin_head('Careers: job posts', 'jobs'); ?>
<div class="grid g4" style="margin-bottom:18px">
  <div class="card stat"><small>Open roles</small><b><?= $open ?></b><span>live on the website</span></div>
  <div class="card stat"><small>All job posts</small><b><?= count($rows) ?></b><span>incl. drafts and closed</span></div>
  <div class="card stat hl"><small>New applications</small><b><?= array_sum(array_column($rows, 'n_new')) ?></b><span>waiting for review</span></div>
  <div class="card stat"><small>Total applications</small><b><?= array_sum(array_column($rows, 'n_all')) ?></b><span>all time</span></div>
</div>
<div class="bar"><span class="grow muted">Job posts appear on the Careers page as soon as their status is <b>Open</b>.</span>
  <a class="btn ghost" href="?export=1">⬇ Export jobs (CSV)</a><a class="btn ghost" href="applications.php">View applications</a>
  <?php if (can('careers.manage')): ?><a class="btn" href="job-edit.php">+ Add job</a><?php endif; ?></div>
<div class="card"><div class="tw"><table><thead><tr><th>Role</th><th>Type · Location</th><th>Closing</th><th>Applications</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $st = job_state($r); ?><tr>
 <td><b><?= e($r['title']) ?></b><br><span class="muted small"><?= e($r['company']) ?><?= $r['department'] ? ' · ' . e($r['department']) : '' ?></span></td>
 <td class="small"><?= e($r['type'] ?: '—') ?><br><span class="muted"><?= e($r['location'] ?: '—') ?></span></td>
 <td class="small"><?= $r['closing_date'] ? e(dt($r['closing_date'])) : '<span class="muted">No date</span>' ?></td>
 <td><a href="applications.php?job=<?= $r['id'] ?>"><b><?= (int)$r['n_all'] ?></b></a><?= $r['n_new'] ? ' <span class="b b-new">' . (int)$r['n_new'] . ' new</span>' : '' ?></td>
 <td><?= badge($st) ?><?= $st !== $r['status'] ? '<br><span class="muted small">closing date passed</span>' : '' ?></td>
 <td class="num"><div class="actions">
   <?php if ($r['status'] !== 'Draft'): ?><a class="btn sm ghost" href="../job.php?j=<?= e($r['slug']) ?>" target="_blank">View</a><?php endif; ?>
   <?php if (can('careers.manage')): ?><a class="btn sm ghost" href="job-edit.php?id=<?= $r['id'] ?>">Edit</a>
   <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="<?= $r['status'] === 'Open' ? 'Closed' : 'Open' ?>"><button class="btn sm ghost"><?= $r['status'] === 'Open' ? 'Close' : ($r['status'] === 'Draft' ? 'Publish' : 'Reopen') ?></button></form><?php endif; ?>
 </div></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">No job posts yet. Click “Add job”.</td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
