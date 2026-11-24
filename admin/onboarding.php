<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_perm('careers.manage');
    $a = q1("SELECT a.*, j.title jtitle, j.company jcompany FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.id=? AND a.status IN ('Accepted','Hired')", [(int)post('app')]);
    if ($a) {
        $mail = hr_set_status($a, 'Hired', '', admin_user()['name'], post('notify', 1) === '1', post('start', 10) ?: null);
        flash($a['name'] . ' is now Hired and their onboarding checklist was created.' . hr_notice($mail));
        redirect('onboarding-view.php?id=' . (int)qv("SELECT id FROM onboardings WHERE application_id=?", [$a['id']]));
    }
    flash('That applicant must be Accepted before onboarding can start.', 'err'); redirect('onboarding.php');
}
$f = (string)($_GET['s'] ?? 'In progress'); if (!in_array($f, ['In progress', 'Completed', 'all'], true)) $f = 'In progress';
$rows = q("SELECT o.*, a.name, a.email, a.id aid, j.title jtitle, j.company jcompany FROM onboardings o JOIN applications a ON a.id=o.application_id JOIN jobs j ON j.id=a.job_id" . ($f !== 'all' ? " WHERE o.status=?" : '') . " ORDER BY o.id DESC", $f !== 'all' ? [$f] : []);
$cnt = array_column(q("SELECT status, COUNT(*) n FROM onboardings GROUP BY status"), 'n', 'status');
$ready = q("SELECT a.id,a.name,a.status,j.title jtitle FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.status IN ('Accepted','Hired') AND NOT EXISTS (SELECT 1 FROM onboardings o WHERE o.application_id=a.id) ORDER BY a.updated_at DESC");
admin_head('New hires & onboarding', 'onboarding'); ?>
<?php if ($ready && can('careers.manage')): ?>
<div class="card attn"><h3>Ready to onboard</h3><p class="muted" style="margin-top:-8px">These applicants were accepted. Mark them as hired to create their onboarding checklist and send the welcome email.</p>
  <?php foreach ($ready as $r): ?><form method="post" class="ready-row"><?= csrf_field() ?><input type="hidden" name="app" value="<?= $r['id'] ?>"><input type="hidden" name="notify" value="1">
    <div><a href="application.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><span class="muted small"><?= e($r['jtitle']) ?></span></div>
    <label class="inl">Start date <input type="date" name="start"></label><button class="btn sm">Hire &amp; start onboarding</button></form><?php endforeach; ?></div>
<?php endif; ?>
<div class="tabs"><?php foreach (['In progress' => 'In progress', 'Completed' => 'Completed', 'all' => 'All'] as $k => $l): ?><a href="?s=<?= urlencode($k) ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= $l ?><i><?= $k === 'all' ? array_sum($cnt) : (int)($cnt[$k] ?? 0) ?></i></a><?php endforeach; ?></div>
<div class="card"><div class="tw"><table><thead><tr><th>New hire</th><th>Role</th><th>Start date</th><th style="min-width:200px">Progress</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $o): [$d, $t] = hr_onb_progress((int)$o['id']); ?><tr>
  <td><a href="onboarding-view.php?id=<?= $o['id'] ?>"><b><?= e($o['name']) ?></b></a><br><span class="muted small"><?= e($o['email']) ?></span></td>
  <td><?= e($o['jtitle']) ?><br><span class="muted small"><?= e($o['jcompany']) ?></span></td>
  <td class="small"><?= $o['start_date'] ? e(dt($o['start_date'])) : '<span class="muted">Not set</span>' ?></td>
  <td><?= hr_progress_bar($d, $t) ?></td>
  <td><?= badge($o['status']) ?></td>
  <td class="num"><a class="btn sm" href="onboarding-view.php?id=<?= $o['id'] ?>">Open</a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">No one here yet. Mark an accepted applicant as <b>Hired</b> to start their onboarding.</td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
