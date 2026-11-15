<?php
require __DIR__ . '/lib/bootstrap.php';
$j = job_by_slug((string)($_GET['j'] ?? ''));
$active = 'careers.php';
if (!$j) {
    http_response_code(404);
    $title = 'Role not found | Empre Technologies Careers'; $desc = 'This role is no longer listed.';
    include __DIR__ . '/inc/head.php'; ?>
<section class="section"><div class="container" style="text-align:center;max-width:640px"><div class="done-ico" style="background:var(--brand-50);color:var(--brand)"><?= svg_icon('briefcase') ?></div>
<h1 style="font-size:34px">That role is no longer listed</h1><p>It may have been filled or removed. See what we are hiring for right now.</p><a class="btn btn-primary" href="careers.php">View open roles <?= svg_icon('arrow') ?></a></div></section>
<?php include __DIR__ . '/inc/foot.php'; exit; }
$open = job_is_open($j); $me = applicant_user();
$mine = $me ? q1("SELECT id,code,status FROM applications WHERE job_id=? AND applicant_id=?", [$j['id'], $me['id']]) : null;
$title = $j['title'] . ' | Careers at ' . $j['company']; $desc = mb_strimwidth((string)$j['summary'], 0, 155, '…');
$url = site_url('job.php?j=' . $j['slug']);
include __DIR__ . '/inc/head.php';
$ld = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $j['title'], 'description' => trim($j['role_desc'] . "\n" . implode("\n", lines($j['responsibilities']))),
       'datePosted' => substr((string)$j['created_at'], 0, 10), 'employmentType' => job_employment_type((string)$j['type']),
       'hiringOrganization' => ['@type' => 'Organization', 'name' => $j['company']], 'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'NG']], 'directApply' => true];
if ($j['closing_date']) $ld['validThrough'] = $j['closing_date'];
if ($open) echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
$resp = lines($j['responsibilities']); $req = lines($j['requirements']); $nice = lines($j['nice_to_have']); $ben = lines($j['benefits']); $ms = job_milestones($j['success']);
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><a href="careers.php">Careers</a><span>/</span><span class="cur"><?= e($j['title']) ?></span></div>
  <h1><?= e($j['title']) ?></h1><p><?= e($j['summary']) ?></p>
  <ul class="banner-stats">
    <li><?= svg_icon('briefcase') ?><?= e($j['company']) ?></li>
    <?php if ($j['type']): ?><li><?= svg_icon('clock') ?><?= e($j['type']) ?></li><?php endif; ?>
    <?php if ($j['location']): ?><li><?= svg_icon('pin') ?><?= e(trim($j['location'] . ($j['work_mode'] ? ' · ' . $j['work_mode'] : ''))) ?></li><?php endif; ?>
  </ul>
</div></section>
<section class="section"><div class="container course-layout">
  <div class="course-main job-main">
    <?php if (!$open): ?><div class="alert fail">Applications for this role are closed.</div><?php endif; ?>
    <h2>The role</h2><p><?= nl2br(e($j['role_desc'])) ?></p>
    <?php if ($resp): ?><h2>What you will do</h2><ul class="checks"><?php foreach ($resp as $r): ?><li><span class="tick"><?= svg_icon('check') ?></span><span><?= e($r) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($req): ?><h2>What we are looking for</h2><ul class="checks"><?php foreach ($req as $r): ?><li><span class="tick"><?= svg_icon('check') ?></span><span><?= e($r) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($nice): ?><h2>Nice to have</h2><div class="meta" style="margin:0 0 30px"><?php foreach ($nice as $n): ?><span class="chip"><?= svg_icon('spark') ?><?= e($n) ?></span><?php endforeach; ?></div><?php endif; ?>
    <?php if ($ms): ?><h2>How success looks</h2><div class="milestones"><?php foreach ($ms as $m): ?><div class="ms"><b><?= e($m[0] ?: 'Goal') ?></b><p><?= e($m[1]) ?></p></div><?php endforeach; ?></div><?php endif; ?>
    <?php if ($ben): ?><h2>What we offer</h2><ul class="checks"><?php foreach ($ben as $r): ?><li><span class="tick"><?= svg_icon('check') ?></span><span><?= e($r) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if (trim((string)$j['about_company'])): ?><h2>About <?= e($j['company']) ?></h2><?php foreach (svc_paras($j['about_company']) as $p): ?><p><?= e($p) ?></p><?php endforeach; ?><?php endif; ?>
    <h2>Hiring process</h2>
    <ol class="mini-steps"><?php foreach (HIRING_STEPS as $i => [$h, $t]): ?><li><span><?= $i + 1 ?></span><div><b><?= e($h) ?></b><small><?= e($t) ?></small></div></li><?php endforeach; ?></ol>
    <p class="muted" style="margin-top:26px">We are an equal opportunity employer. We welcome applications from everyone.</p>
  </div>
  <aside class="aside" id="apply-card">
    <div class="price"><small>Status</small><?= $open ? '<span class="st st-open">Open for applications</span>' : '<span class="st st-closed">Applications closed</span>' ?></div>
    <dl>
      <div><dt>Company</dt><dd><?= e($j['company']) ?></dd></div>
      <?php if ($j['department']): ?><div><dt>Team</dt><dd><?= e($j['department']) ?></dd></div><?php endif; ?>
      <?php if ($j['type']): ?><div><dt>Type</dt><dd><?= e($j['type']) ?></dd></div><?php endif; ?>
      <?php if ($j['location']): ?><div><dt>Location</dt><dd><?= e(trim($j['location'] . ($j['work_mode'] ? ' · ' . $j['work_mode'] : ''))) ?></dd></div><?php endif; ?>
      <?php if ($j['reports_to']): ?><div><dt>Reports to</dt><dd><?= e($j['reports_to']) ?></dd></div><?php endif; ?>
      <?php if ($j['pay']): ?><div><dt>Pay</dt><dd><?= e($j['pay']) ?></dd></div><?php endif; ?>
      <?php if ($j['start_date']): ?><div><dt>Start date</dt><dd><?= e($j['start_date']) ?></dd></div><?php endif; ?>
      <?php if ($j['closing_date']): ?><div><dt>Apply by</dt><dd><?= e(dt($j['closing_date'])) ?></dd></div><?php endif; ?>
    </dl>
    <?php if ($mine): ?>
      <a class="btn btn-dark" href="portal-app.php?id=<?= (int)$mine['id'] ?>">View my application <?= svg_icon('arrow') ?></a>
      <p class="help">You applied (<?= e($mine['code']) ?>). Status: <b><?= e($mine['status']) ?></b></p>
    <?php elseif ($open): ?>
      <a class="btn btn-primary" href="apply.php?j=<?= e($j['slug']) ?>">Apply now <?= svg_icon('arrow') ?></a>
      <p class="help">Takes about 5 minutes. Have your CV and LinkedIn link ready.</p>
    <?php else: ?>
      <a class="btn btn-dark" href="careers.php">See other open roles <?= svg_icon('arrow') ?></a>
    <?php endif; ?>
    <div class="share"><span><?= svg_icon('share') ?>Share</span>
      <a href="https://wa.me/?text=<?= rawurlencode($j['title'] . ' at ' . $j['company'] . ' ' . $url) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($url) ?>" target="_blank" rel="noopener">LinkedIn</a></div>
    <p class="help">Already applied? <a href="<?= $me ? 'portal.php' : 'portal-login.php' ?>">Track your application</a></p>
  </aside>
</div></section>
<?php if ($open && !$mine): ?><div class="sticky-apply"><a class="btn btn-primary" href="apply.php?j=<?= e($j['slug']) ?>">Apply for this role <?= svg_icon('arrow') ?></a></div><?php endif; ?>
<?php include __DIR__ . '/inc/foot.php'; ?>
