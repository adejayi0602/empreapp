<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
$openJobs = count(array_filter(q("SELECT * FROM jobs"), 'job_is_open'));
$by = array_column(q("SELECT status, COUNT(*) n FROM applications GROUP BY status"), 'n', 'status');
$total = array_sum($by);
$week = (int)qv("SELECT COUNT(*) FROM applications WHERE created_at >= ?", [date('Y-m-d H:i:s', strtotime('-7 days'))]);
$inPipe = (int)(($by['Under Review'] ?? 0) + ($by['Shortlisted'] ?? 0) + ($by['Interview'] ?? 0) + ($by['More Details Needed'] ?? 0));
$onbRows = q("SELECT o.*, a.name, a.id aid, j.title jtitle FROM onboardings o JOIN applications a ON a.id=o.application_id JOIN jobs j ON j.id=a.job_id WHERE o.status='In progress' ORDER BY o.id DESC LIMIT 6");
$onbN = (int)qv("SELECT COUNT(*) FROM onboardings WHERE status='In progress'");
$interviews = q("SELECT a.id,a.name,a.interview_at,j.title jtitle FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.status='Interview' AND a.interview_at >= ? ORDER BY a.interview_at LIMIT 6", [date('Y-m-d H:i:s', strtotime('-1 hour'))]);
$recent = q("SELECT a.id,a.name,a.status,a.created_at,j.title jtitle FROM applications a JOIN jobs j ON j.id=a.job_id ORDER BY a.id DESC LIMIT 7");
$stale = q("SELECT a.id,a.name,a.created_at,j.title jtitle FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.status='Received' AND a.created_at < ? ORDER BY a.created_at LIMIT 5", [date('Y-m-d H:i:s', strtotime('-3 days'))]);
$noOnb = q("SELECT a.id,a.name,j.title jtitle FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.status IN ('Accepted','Hired') AND NOT EXISTS (SELECT 1 FROM onboardings o WHERE o.application_id=a.id) LIMIT 5");
$overdue = (int)qv("SELECT COUNT(*) FROM onboarding_tasks t JOIN onboardings o ON o.id=t.onboarding_id WHERE o.status='In progress' AND t.done=0 AND t.due_date IS NOT NULL AND t.due_date < ?", [date('Y-m-d')]);
$failedMail = (int)qv("SELECT COUNT(*) FROM mail_log WHERE status='failed'");
$jobs = q("SELECT j.id,j.title,j.status,j.closing_date,(SELECT COUNT(*) FROM applications a WHERE a.job_id=j.id) n FROM jobs j ORDER BY j.sort,j.id");
$funnel = ['Received', 'Under Review', 'Shortlisted', 'Interview', 'Accepted', 'Hired']; $fmax = max(1, ...array_map(fn($s) => (int)($by[$s] ?? 0), $funnel));
$attn = count($stale) + count($noOnb) + ($overdue ? 1 : 0) + ($failedMail ? 1 : 0);
admin_head('HR overview', 'hr'); ?>
<div class="grid g4 hr-kpis" style="margin-bottom:18px">
  <div class="card stat"><small>Open roles</small><b><?= $openJobs ?></b><span><a href="jobs.php">Manage job posts</a></span></div>
  <div class="card stat hl"><small>New applications</small><b><?= (int)($by['Received'] ?? 0) ?></b><span><?= $week ?> in the last 7 days</span></div>
  <div class="card stat"><small>In the pipeline</small><b><?= $inPipe ?></b><span><a href="pipeline.php">Open the board</a></span></div>
  <div class="card stat"><small>New hires onboarding</small><b><?= $onbN ?></b><span><a href="onboarding.php">View new hires</a></span></div>
</div>
<div class="grid g21">
 <div>
  <div class="card"><h3>Hiring funnel</h3><div class="funnel">
    <?php foreach ($funnel as $s): $n = (int)($by[$s] ?? 0); ?><div class="fn-row"><span><?= e($s) ?></span><div class="fn-bar"><i style="width:<?= max($n ? 4 : 0, round($n / $fmax * 100)) ?>%"></i></div><b><?= $n ?></b></div><?php endforeach; ?>
    </div><p class="muted small" style="margin:12px 0 0"><?= $total ?> application<?= $total === 1 ? '' : 's' ?> in total · <?= (int)(($by['Rejected'] ?? 0) + ($by['Not Qualified'] ?? 0)) ?> not taken forward</p></div>
  <div class="card"><h3>Recent applications</h3><div class="tw"><table><tbody>
    <?php foreach ($recent as $r): ?><tr><td><a href="application.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><span class="muted small"><?= e($r['jtitle']) ?></span></td><td class="small muted"><?= e(ago($r['created_at'])) ?></td><td class="num"><?= badge($r['status']) ?></td></tr>
    <?php endforeach; if (!$recent): ?><tr><td class="muted">No applications yet.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="card"><h3>Job posts</h3><div class="tw"><table><tbody>
    <?php foreach ($jobs as $j): ?><tr><td><b><?= e($j['title']) ?></b></td><td><?= badge(job_state($j)) ?></td><td class="num"><a href="applications.php?job=<?= $j['id'] ?>"><?= (int)$j['n'] ?> application<?= (int)$j['n'] === 1 ? '' : 's' ?></a></td></tr><?php endforeach; ?></tbody></table></div></div>
 </div>
 <div>
  <div class="card <?= $attn ? 'attn' : '' ?>"><h3>Needs attention</h3>
   <?php if (!$attn): ?><p class="muted">All clear. Nothing is waiting on you.</p><?php endif; ?>
   <ul class="todo">
    <?php foreach ($stale as $r): ?><li><a href="application.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b> has been waiting for a first review since <?= e(dt($r['created_at'])) ?></a><small><?= e($r['jtitle']) ?></small></li><?php endforeach; ?>
    <?php foreach ($noOnb as $r): ?><li><a href="application.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b> is accepted: start onboarding</a><small><?= e($r['jtitle']) ?></small></li><?php endforeach; ?>
    <?php if ($overdue): ?><li><a href="onboarding.php"><b><?= $overdue ?> onboarding task<?= $overdue === 1 ? ' is' : 's are' ?> overdue</b></a></li><?php endif; ?>
    <?php if ($failedMail): ?><li><a href="careers-maillog.php?status=failed"><b><?= $failedMail ?> email<?= $failedMail === 1 ? '' : 's' ?> failed to send</b>: resend</a></li><?php endif; ?>
   </ul></div>
  <div class="card"><h3>Upcoming interviews</h3>
   <?php foreach ($interviews as $r): ?><div class="mini-row"><a href="application.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><span class="muted small"><?= e($r['jtitle']) ?></span><span class="when"><?= e(date('D j M · g:i A', strtotime($r['interview_at']))) ?></span></div><?php endforeach; ?>
   <?php if (!$interviews): ?><p class="muted">No interviews scheduled. Set one from an application.</p><?php endif; ?></div>
  <div class="card"><h3>Onboarding in progress</h3>
   <?php foreach ($onbRows as $o): [$d, $t] = hr_onb_progress((int)$o['id']); ?><div class="mini-row stack"><a href="onboarding-view.php?id=<?= $o['id'] ?>"><b><?= e($o['name']) ?></b></a><span class="muted small"><?= e($o['jtitle']) ?><?= $o['start_date'] ? ' · starts ' . e(dt($o['start_date'])) : '' ?></span><?= hr_progress_bar($d, $t) ?></div><?php endforeach; ?>
   <?php if (!$onbRows): ?><p class="muted">No one is onboarding right now. When you mark an applicant as <b>Hired</b>, their checklist appears here.</p><?php endif; ?></div>
 </div>
</div>
<?php admin_foot();
