<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Training Courses | Empre Technologies Ltd';
$desc  = 'Enrol in Front End Development, Cloud Computing, Cyber Security, DevOps Engineering and Product Design with Empre Technologies, virtual or in-person.';
$active = 'training.php';
$courses = active_courses();
include __DIR__ . '/inc/head.php';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><span class="cur">Training</span></div>
  <h1>Training</h1><p>Technology Training &amp; Development: practical learning designed to build confidence, capability and career-ready skills, delivered virtually or in person.</p>
</div></section>

<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Courses</span><h2>Choose a course and start learning</h2>
  <p>Select a course to view what you will learn and complete your enrolment online. Every applicant receives a unique student ID.</p></div>
  <?php if (!$courses): ?><p style="text-align:center">New courses are coming soon. Please <a href="contact.php" style="color:var(--brand);font-weight:700">contact us</a> for details.</p><?php endif; ?>
  <div class="cards-3">
  <?php foreach ($courses as $i => $c): ?>
    <a class="card course reveal d<?= $i % 3 ?>" href="course.php?c=<?= e($c['slug']) ?>">
      <span class="num"><?= sprintf('%02d', $i + 1) ?></span>
      <div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-<?= e($c['icon'] ?: 'code') ?>"/></svg></div>
      <h3><?= e($c['title']) ?></h3>
      <p><?= e($c['summary']) ?></p>
      <div class="meta">
        <?php if ($c['duration']): ?><span class="chip"><svg class="ic" viewBox="0 0 24 24"><use href="#i-clock"/></svg><?= e($c['duration']) ?></span><?php endif; ?>
        <?php if ($c['mode']): ?><span class="chip"><svg class="ic" viewBox="0 0 24 24"><use href="#i-globe"/></svg><?= e($c['mode']) ?></span><?php endif; ?>
      </div>
      <div class="fee-line"><?= $c['fee'] > 0 ? e(money($c['fee'])) : '<small>Contact us for course fee</small>' ?></div>
      <span class="more">View details &amp; enrol <svg class="ic" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></span>
    </a>
  <?php endforeach; ?>
  </div>
</div></section>

<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">How enrolment works</span><h2>Three simple steps</h2></div>
  <div class="pillars">
    <div class="pillar light reveal d1"><div class="ico"><svg class="ic" viewBox="0 0 24 24"><use href="#i-target"/></svg></div><h3>1. Pick a course</h3><p>Open the course page to see the topics covered, who it is for and how it is delivered.</p></div>
    <div class="pillar light reveal d2"><div class="ico"><svg class="ic" viewBox="0 0 24 24"><use href="#i-pen"/></svg></div><h3>2. Enrol online</h3><p>Complete the short enrolment form. Your unique student ID is shown immediately.</p></div>
    <div class="pillar light reveal d3"><div class="ico"><svg class="ic" viewBox="0 0 24 24"><use href="#i-mail"/></svg></div><h3>3. Get your invoice</h3><p>Our team reviews your application and sends your invoice and next steps by email.</p></div>
  </div>
</div></section>

<div class="cta-wrap"><div class="container"><div class="cta reveal">
  <div class="txt"><h2>Training for your whole team?</h2><p>Tell us about your organisation and we will tailor a programme for your users and IT staff.</p></div>
  <a class="btn btn-white" href="contact.php">Contact Us <svg class="ic" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a>
</div></div></div>
<?php include __DIR__ . '/inc/foot.php'; ?>
