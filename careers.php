<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Careers | Empre Technologies Ltd'; $desc = 'Join Empre Technologies and our products, including Conversa AI. See open roles, apply online and track your application.'; $active = 'careers.php';
$jobs = open_jobs();
$depts = array_values(array_unique(array_filter(array_map(fn($j) => $j['department'], $jobs))));
$me = applicant_user();
include __DIR__ . '/inc/head.php';
?>
<section class="banner careers-banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><span class="cur">Careers</span></div>
  <h1>Build the future of business technology with us</h1>
  <p>Real ownership, direct access to the founder and a visible impact from your first week. See what is open and apply in minutes.</p>
  <div class="slide-actions" style="margin-top:30px">
    <a class="btn btn-primary" href="#roles">See open roles <?= svg_icon('arrow') ?></a>
    <a class="btn btn-outline" href="<?= $me ? 'portal.php' : 'portal-login.php' ?>"><?= $me ? 'My applications' : 'Track my application' ?></a>
  </div>
  <ul class="banner-stats"><li><b><?= count($jobs) ?></b> open role<?= count($jobs) === 1 ? '' : 's' ?></li><li><b>Remote-friendly</b> culture</li><li><b>Early-stage</b> team, real ownership</li></ul>
</div></section>

<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Why join us</span><h2>Grow with a team that builds things that matter</h2>
  <p>We help Nigerian businesses stop losing customers and move faster with technology. You will help us do it.</p></div>
  <div class="cards-4">
    <div class="card reveal d1"><div class="ico"><?= svg_icon('target') ?></div><h3>Real ownership</h3><p>Early-stage means your work is visible. You will own outcomes, not just tasks.</p></div>
    <div class="card reveal d2"><div class="ico"><?= svg_icon('handshake') ?></div><h3>Direct founder access</h3><p>Work closely with the founder and shape the product and the company.</p></div>
    <div class="card reveal d3"><div class="ico"><?= svg_icon('grad') ?></div><h3>Learn and grow</h3><p>Training, a learning budget and growth opportunities as the company grows.</p></div>
    <div class="card reveal d1"><div class="ico"><?= svg_icon('globe') ?></div><h3>Friendly and remote-friendly</h3><p>A fast-moving, supportive culture that respects how good work gets done.</p></div>
  </div>
</div></section>

<section class="section soft" id="roles"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Open roles</span><h2>Find your next role</h2>
  <p>Pick a role to read the full description and apply. Every application gets a reference number and a private portal to track progress.</p></div>
  <?php if ($jobs): ?>
  <div class="job-tools reveal">
    <input type="search" id="job-q" placeholder="Search roles…" aria-label="Search roles">
    <div class="job-filter" role="group" aria-label="Filter by team"><button type="button" class="on" data-d="">All</button><?php foreach ($depts as $d): ?><button type="button" data-d="<?= e($d) ?>"><?= e($d) ?></button><?php endforeach; ?></div>
  </div>
  <div class="job-list" id="job-list">
    <?php foreach ($jobs as $i => $j): ?>
    <a class="job-card reveal d<?= $i % 3 ?>" href="job.php?j=<?= e($j['slug']) ?>" data-d="<?= e($j['department']) ?>" data-t="<?= e(strtolower($j['title'] . ' ' . $j['department'] . ' ' . $j['company'] . ' ' . $j['summary'])) ?>">
      <div class="jc-ico"><?= svg_icon($j['icon'] ?: 'users') ?></div>
      <div class="jc-body">
        <div class="jc-top"><span class="chip"><?= e($j['company']) ?></span><?php if ($j['department']): ?><span class="chip"><?= e($j['department']) ?></span><?php endif; ?></div>
        <h3><?= e($j['title']) ?></h3>
        <p><?= e($j['summary']) ?></p>
        <ul class="jc-meta">
          <?php if ($j['type']): ?><li><?= svg_icon('briefcase') ?><?= e($j['type']) ?></li><?php endif; ?>
          <?php if ($j['location'] || $j['work_mode']): ?><li><?= svg_icon('pin') ?><?= e(trim($j['location'] . ' ' . ($j['work_mode'] ? '· ' . $j['work_mode'] : ''))) ?></li><?php endif; ?>
          <?php if ($j['closing_date']): ?><li><?= svg_icon('calendar') ?>Apply by <?= e(dt($j['closing_date'])) ?></li><?php endif; ?>
        </ul>
      </div>
      <span class="jc-go">View &amp; apply <?= svg_icon('arrow') ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <p class="job-none" id="job-none" hidden>No roles match your search. Try a different word or clear the filter.</p>
  <script>(function(){var q=document.getElementById('job-q'),l=document.getElementById('job-list'),n=document.getElementById('job-none'),d='';
  function f(){var t=q.value.toLowerCase().trim(),c=0;l.querySelectorAll('.job-card').forEach(function(a){var ok=(!d||a.dataset.d===d)&&(!t||a.dataset.t.indexOf(t)>-1);a.hidden=!ok;if(ok)c++});n.hidden=c>0}
  q.addEventListener('input',f);document.querySelectorAll('.job-filter button').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.job-filter button').forEach(function(x){x.classList.remove('on')});b.classList.add('on');d=b.dataset.d;f()})});})();</script>
  <?php else: ?>
  <div class="empty-roles reveal"><div class="ico"><?= svg_icon('briefcase') ?></div><h3>No open roles right now</h3><p>We are not hiring at the moment, but we are always glad to hear from talented people. Send us a note and we will keep you in mind.</p><a class="btn btn-primary" href="contact.php">Get in touch <?= svg_icon('arrow') ?></a></div>
  <?php endif; ?>
</div></section>

<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Hiring process</span><h2>Simple, fast and respectful of your time</h2><p>Here is what to expect after you apply.</p></div>
  <ol class="steps">
    <?php foreach (HIRING_STEPS as $i => [$h, $t]): ?><li class="step reveal d<?= $i % 4 ?>"><span class="n"><?= sprintf('%02d', $i + 1) ?></span><h3><?= e($h) ?></h3><p><?= e($t) ?></p></li><?php endforeach; ?>
  </ol>
  <div class="portal-cta reveal"><div class="ico"><?= svg_icon('lock') ?></div><div><h3>Already applied?</h3><p>Sign in to your applicant portal to follow your progress, see your application status and upload anything we ask for.</p></div><a class="btn btn-dark" href="<?= $me ? 'portal.php' : 'portal-login.php' ?>"><?= $me ? 'Open my portal' : 'Sign in to portal' ?> <?= svg_icon('arrow') ?></a></div>
  <p class="lead-note reveal">We are an equal opportunity employer. We welcome applications from everyone.</p>
</div></section>
<?php include __DIR__ . '/inc/foot.php'; ?>
