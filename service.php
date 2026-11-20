<?php
require __DIR__ . '/lib/bootstrap.php';
$active = 'services.php';
$svc = null;
foreach (site_services() as $__s) if ($__s['slug'] === (string)($_GET['s'] ?? '')) { $svc = $__s; break; }
if (!$svc) {
    http_response_code(404); $title = 'Service not found | Empre Technologies'; $desc = 'This service is not listed.';
    include __DIR__ . '/inc/head.php'; ?>
<section class="section"><div class="container" style="text-align:center;max-width:640px"><h1 style="font-size:34px">That service is not listed</h1><p>See everything Empre Technologies offers.</p><a class="btn btn-primary" href="services.php">View all services <?= svg_icon('arrow') ?></a></div></section>
<?php include __DIR__ . '/inc/foot.php'; exit; }
$title = $svc['title'] . ' in Nigeria | Empre Technologies';
$desc  = mb_strimwidth(trim((string)$svc['short']), 0, 158, '…');
$cta = trim((string)$svc['cta_url']) !== '' ? svc_link(trim($svc['cta_url'])) : 'contact.php?service=' . rawurlencode($svc['slug']);
$lbl = trim((string)$svc['cta_label']) !== '' ? $svc['cta_label'] : 'Request a quote';
$related = array_values(array_filter(site_services(), fn($x) => $x['slug'] !== $svc['slug'] && $x['area_title'] === $svc['area_title']));
$others = $related ?: array_slice(array_values(array_filter(site_services(), fn($x) => $x['slug'] !== $svc['slug'])), 0, 3);
include __DIR__ . '/inc/head.php';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><a href="services.php">Our Services</a><span>/</span><span class="cur"><?= e($svc['title']) ?></span></div>
  <h1><?= e($svc['title']) ?></h1><p><?= e($svc['short']) ?></p>
</div></section>
<section class="section"><div class="container course-layout">
  <div class="course-main job-main">
    <h2>About this service</h2>
    <?php foreach (svc_paras($svc['description'] ?: $svc['short']) as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
    <?php if (svc_lines($svc['points'])): ?><h2>What is included</h2>
    <ul class="checks"><?php foreach (svc_lines($svc['points']) as $pt): ?><li><span class="tick"><?= svg_icon('check') ?></span><span><?= e($pt) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    <h2>How an engagement works</h2>
    <ol class="steps" style="grid-template-columns:1fr">
      <?php foreach ([['Discover', 'We learn about your goals, your current environment and any constraints.'], ['Recommend', 'We agree the scope, approach, deliverables and timeline before work begins.'], ['Implement', 'We deliver with clear communication at every stage.'], ['Enable & support', 'We document, hand over, train your people and provide agreed follow-up.']] as $i => [$a, $b]): ?>
      <li class="step"><span class="n"><?= sprintf('%02d', $i + 1) ?></span><h3><?= e($a) ?></h3><p><?= e($b) ?></p></li><?php endforeach; ?>
    </ol>
    <p class="lead-note">Scope, service levels, timelines and fees are agreed with you before work starts.</p>
  </div>
  <aside class="aside">
    <div class="price"><small>Part of</small><b style="font-size:18px"><?= e($svc['area_title']) ?></b></div>
    <a class="btn btn-primary" href="<?= e($cta) ?>"<?= preg_match('~^https?://~i', $cta) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($lbl) ?> <?= svg_icon('arrow') ?></a>
    <p class="help">Prefer to talk? Call <a href="tel:<?= e(preg_replace('/[^+\d]/', '', setting('company_phone'))) ?>"><b><?= e(setting('company_phone')) ?></b></a> or email <a href="mailto:<?= e(setting('company_email')) ?>"><b><?= e(setting('company_email')) ?></b></a>.</p>
  </aside>
</div></section>
<?php if ($others): ?><section class="section soft"><div class="container">
  <div class="sec-head center"><span class="eyebrow">Related</span><h2>More from <?= e($related ? $svc['area_title'] : 'Empre Technologies') ?></h2></div>
  <div class="cards-4" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
  <?php foreach (array_slice($others, 0, 3) as $o): ?><a class="card" href="service.php?s=<?= e($o['slug']) ?>"><h3><?= e($o['title']) ?></h3><p><?= e($o['short']) ?></p></a><?php endforeach; ?>
  </div></div></section><?php endif; ?>
<div class="cta-wrap"><div class="container"><div class="cta reveal">
  <div class="txt"><h2>Need help with <?= e($svc['title']) ?>?</h2><p>Tell us what you need and we'll recommend the right approach.</p></div>
  <a class="btn btn-white" href="contact.php?service=<?= e(rawurlencode($svc['slug'])) ?>">Get in Touch <?= svg_icon('arrow') ?></a>
</div></div></div>
<?php include __DIR__ . '/inc/foot.php'; ?>
