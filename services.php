<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Our Services | Empre Technologies Ltd';
$desc = 'IT, cloud and cybersecurity, software and digital solutions, technology training and consulting from Empre Technologies.';
$active = 'services.php';
include __DIR__ . '/inc/head.php';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><span class="cur">Our Services</span></div>
  <h1>Our Services</h1><p>Four connected service areas designed to support the full technology journey.</p>
</div></section>
<?php $__areas = site_areas(); $__n = 0; ?>
<section class="section" style="padding-bottom:20px"><div class="container">
  <div class="sec-head center reveal" style="margin-bottom:0"><span class="eyebrow">What we offer</span><h2>Integrated technology services</h2>
  <p>From keeping your systems running and secure to building the products, brand and skills that help you grow. Service scope can be tailored to project requirements, business size and operational needs.</p></div></div></section>
<?php foreach ($__areas as $__ai => $a): ?>
<section class="area" id="<?= e($a['slug']) ?>"><div class="container">
  <div class="area-head reveal"><span class="eyebrow">Service area <?= sprintf('%02d', $__ai + 1) ?></span><h2><?= e($a['title']) ?></h2><p><?= e($a['tagline']) ?></p></div>
  <?php foreach ($a['services'] as $s): ?><?php $__n++; $__cta = trim((string)$s['cta_url']) !== '' ? $s['cta_url'] : 'contact.php?service=' . rawurlencode($s['slug']);
$__lbl = trim((string)$s['cta_label']) !== '' ? $s['cta_label'] : 'Enquire about this'; ?>
<article class="svc<?= $__n % 2 === 0 ? ' flip' : '' ?>" id="<?= e($s['slug']) ?>">
  <div class="svc-art reveal"><span class="tag">SERVICE AREA <?= sprintf('%02d', $__ai + 1) ?></span><div class="big"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-<?= e(svc_icon($s['icon'])) ?>"/></svg></div></div>
  <div class="reveal d1"><span class="eyebrow">Service <?= sprintf('%02d', $__n) ?></span><h2><?= e($s['title']) ?></h2>
    <?php foreach (svc_paras($s['description'] ?: $s['short']) as $__p): ?><p><?= e($__p) ?></p><?php endforeach; ?>
    <ul class="checks"><?php foreach (svc_lines($s['points']) as $__pt): ?><li><span class="tick"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-check"/></svg></span><span><?= e($__pt) ?></span></li><?php endforeach; ?></ul>
    <a class="btn btn-primary" style="margin-top:14px" href="<?= e($__cta) ?>"><?= e($__lbl) ?> <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a></div>
</article>
  <?php endforeach; ?>
  <?php if (trim((string)$a['outcome']) !== ''): ?><div class="outcome reveal"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-target"/></svg><p><b>Outcome:</b> <?= e($a['outcome']) ?></p></div><?php endif; ?>
</div></section>
<?php endforeach; ?>
<?php if (!$__areas): ?><section class="section"><div class="container"><p style="text-align:center">Our service list is being updated. Please <a href="contact.php" style="color:var(--brand);font-weight:700">contact us</a> to talk about your needs.</p></div></section><?php endif; ?>
<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Working With Us</span><h2>A straightforward path from need to outcome</h2>
  <p>A consistent engagement approach for projects, support and training.</p></div>
  <ol class="steps"><li class="step reveal d0"><span class="n">01</span><h3>Discover</h3><p>Understand goals, current environment and constraints.</p></li><li class="step reveal d1"><span class="n">02</span><h3>Recommend</h3><p>Agree scope, approach, deliverables and timeline.</p></li><li class="step reveal d2"><span class="n">03</span><h3>Implement</h3><p>Deliver the solution with clear communication.</p></li><li class="step reveal d3"><span class="n">04</span><h3>Enable</h3><p>Document, hand over and equip users or teams.</p></li><li class="step reveal d0"><span class="n">05</span><h3>Support</h3><p>Provide agreed follow-up, optimisation or ongoing support.</p></li></ol>
  <p class="lead-note reveal">Every engagement is scoped to the client's requirements. Specific deliverables, service levels, timelines and fees are agreed before work begins.</p>
</div></section><div class="cta-wrap"><div class="container"><div class="cta reveal">
  <div class="txt"><h2>Not sure which service you need?</h2><p>Tell us about your business and we'll recommend the right solution.</p></div>
  <a class="btn btn-white" href="contact.php">Get in Touch <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a>
</div></div></div>
<?php include __DIR__ . '/inc/foot.php'; ?>
