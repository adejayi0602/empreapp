<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Empre Technologies Ltd | Technology that empowers businesses to grow';
$desc = 'Empre Technologies helps businesses transform their operations through IT infrastructure, cloud, cybersecurity, software development, digital design and professional technology training.';
$active = 'index.php';
include __DIR__ . '/inc/head.php';
?>
<section class="hero" aria-roledescription="carousel" aria-label="Featured">
  <div class="slides"><div class="slide is-active" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-1.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">Empre Technologies</span><h1>Technology that <em>empowers</em> businesses to grow.</h1><p class="lead">IT infrastructure, cloud, cybersecurity, software, digital design and professional training, from one trusted partner.</p>
        <div class="slide-actions"><a class="btn btn-primary" href="services.php">Explore Services <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a><a class="btn btn-outline" href="contact.php">Contact Us</a></div>
      </div></div></div><div class="slide" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-2.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">Enable</span><h1>Build reliable <em>digital</em> foundations</h1><p class="lead">Technical support, Microsoft 365, identity and access, and cloud infrastructure that keep your people productive.</p>
        <div class="slide-actions"><a class="btn btn-primary" href="services.php#it-cloud-cybersecurity">IT, Cloud &amp; Cybersecurity <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a><a class="btn btn-outline" href="contact.php">Contact Us</a></div>
      </div></div></div><div class="slide" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-3.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">Protect</span><h1>Secure systems, devices and <em>data</em></h1><p class="lead">Endpoint management with Intune, Defender for Endpoint and Zoho Endpoint Central, with monitoring and compliance built in.</p>
        <div class="slide-actions"><a class="btn btn-primary" href="services.php#endpoint-management-security">Protect Your Business <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a><a class="btn btn-outline" href="contact.php">Contact Us</a></div>
      </div></div></div><div class="slide" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-4.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">Empower · Products</span><h1>Digital products and brands that <em>grow</em> with you</h1><p class="lead">Websites, web applications, product design and branding that support customer engagement and business growth.</p>
        <div class="slide-actions"><a class="btn btn-primary" href="services.php#software-digital-solutions">Software &amp; Digital Solutions <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a><a class="btn btn-outline" href="contact.php">Contact Us</a></div>
      </div></div></div><div class="slide" aria-roledescription="slide">
      <div class="slide-bg" style="background-image:url(assets/img/hero-5.svg)"></div>
      <div class="container"><div class="slide-content anim">
        <span class="eyebrow">Empower · People</span><h1>Build skills that <em>power</em> careers and teams</h1><p class="lead">Practical, career-ready training in front-end, cloud, cybersecurity, DevOps and product design, virtual or in person.</p>
        <div class="slide-actions"><a class="btn btn-primary" href="training.php">Explore Training <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a><a class="btn btn-outline" href="contact.php">Contact Us</a></div>
      </div></div></div></div>
  <div class="hero-ctrl"><div class="container"><div class="dots"><button aria-label="Go to slide 1"></button><button aria-label="Go to slide 2"></button><button aria-label="Go to slide 3"></button><button aria-label="Go to slide 4"></button><button aria-label="Go to slide 5"></button></div>
    <div class="arrows"><button class="prev" aria-label="Previous slide"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></button><button class="next" aria-label="Next slide"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></button></div></div></div>
</section>
<div class="strip"><div class="container"><div class="strip-grid"><?php foreach (array_slice(site_areas(), 0, 4) as $__a): ?><a class="strip-item" href="services.php#<?= e($__a['slug']) ?>"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-<?= e(svc_icon($__a['icon'])) ?>"/></svg></span><div><b><?= e($__a['title']) ?></b><span><?= count($__a['services']) ?> service<?= count($__a['services']) === 1 ? "" : "s" ?></span></div></a><?php endforeach; ?></div></div></div>
<section class="section"><div class="container split">
  <div class="visual reveal">
    <div class="panel"></div>
    <div class="mark"><img src="assets/img/logo-icon.png" alt="Empre Technologies logo mark"></div>
    <div class="float f2"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-shield"/></svg></span><div><b>Protect</b><small>Systems, devices &amp; data</small></div></div>
    <div class="float f1"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-cloud"/></svg></span><div><b>Enable</b><small>Reliable digital foundations</small></div></div>
  </div>
  <div class="reveal d1">
    <span class="eyebrow">Who We Are</span>
    <h2>A technology partner for organisations and individuals navigating a digital world</h2>
    <p>We are a forward-thinking technology solutions company delivering scalable, secure and practical services tailored to the needs of modern businesses.</p>
    <p>From responsive technical support and modern workplace solutions to digital product development and hands-on training, we help clients solve technology challenges and make progress with confidence.</p>
    <ul class="checks" style="margin:26px 0 34px">
      <li><span class="tick"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-check"/></svg></span><span><b>Businesses.</b> Technology support, transformation and digital delivery.</span></li>
      <li><span class="tick"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-check"/></svg></span><span><b>Startups.</b> Digital foundations, product design and development.</span></li>
      <li><span class="tick"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-check"/></svg></span><span><b>Teams.</b> Workplace productivity, security and capability building.</span></li>
      <li><span class="tick"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-check"/></svg></span><span><b>Individuals.</b> Practical technology training and career development.</span></li>
    </ul>
    <a class="btn btn-primary" href="about.php">Learn more about us <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a>
  </div>
</div></section><section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Positioning</span><h2>Technology that empowers businesses to grow.</h2>
  <p>Empre Technologies is a technology solutions company helping businesses transform their operations through IT infrastructure, cloud solutions, cybersecurity, software development, digital design and professional technology training.</p></div>
  <div class="pillars"><div class="pillar light reveal d1"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-layers"/></svg></div><h3>Enable</h3><p>Build reliable digital foundations.</p></div><div class="pillar light reveal d2"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-shield"/></svg></div><h3>Protect</h3><p>Secure systems, devices and data.</p></div><div class="pillar light reveal d3"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-users"/></svg></div><h3>Empower</h3><p>Develop products, people and capability.</p></div></div>
  <p class="lead-note reveal">We combine technical expertise, innovation and practical business understanding to help organisations operate efficiently, protect their digital assets and scale with confidence.</p>
</div></section><?php $__areas = site_areas(); ?>
<section class="section"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">What We Do</span><h2>Integrated technology services</h2>
  <p>Connected service areas designed to support the full technology journey. Scope can be tailored to your project requirements, business size and operational needs.</p></div>
  <div class="cards-4"><?php foreach ($__areas as $__i => $a): ?><a class="card reveal d<?= $__i % 4 ?>" href="services.php#<?= e($a['slug']) ?>"><span class="num"><?= sprintf('%02d', $__i + 1) ?></span><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-<?= e(svc_icon($a['icon'])) ?>"/></svg></div><h3><?= e($a['title']) ?></h3><p><?= e($a['summary']) ?></p><span class="more">Explore <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></span></a><?php endforeach; ?></div>
</div></section><?php $__svcs = site_services(); ?>
<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Our Services</span><h2>Everything from reliable IT to digital products and training</h2>
  <p>IT, cloud and cybersecurity, software and digital solutions, training and consulting, from one connected partner.</p></div>
  <div class="cards-3"><?php foreach ($__svcs as $__i => $s): ?><a class="card reveal d<?= $__i % 4 ?>" href="services.php#<?= e($s['slug']) ?>"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-<?= e(svc_icon($s['icon'])) ?>"/></svg></div><h3><?= e($s['title']) ?></h3><p><?= e($s['short']) ?></p><span class="more">Learn more <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></span></a><?php endforeach; ?></div>
  <div style="text-align:center;margin-top:48px"><a class="btn btn-dark" href="services.php">View all services <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a></div>
</div></section><section class="section"><div class="container"><div class="tease reveal">
  <div><span class="eyebrow">Technology Training &amp; Development</span><h2>Practical learning for career-ready skills</h2>
  <p>Hands-on programmes designed to build confidence and capability, delivered virtually or in person. Corporate training and team upskilling can be scoped to your organisation's needs.</p>
  <div class="chips-lg" id="tease-courses"><a class="chip-lg" href="training.php">Front End Development</a><a class="chip-lg" href="training.php">Cloud Computing</a><a class="chip-lg" href="training.php">Cyber Security</a><a class="chip-lg" href="training.php">DevOps Engineering</a><a class="chip-lg" href="training.php">Product Design</a></div>
  <a class="btn btn-primary" href="training.php">Explore courses &amp; enrol <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a></div>
  <div class="tease-art" aria-hidden="true"><span><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-grad"/></svg></span></div>
</div></div></section>
<script>fetch('api-courses.php').then(function(r){return r.json()}).then(function(j){if(!j.length)return;document.getElementById('tease-courses').innerHTML=j.map(function(c){var a=document.createElement('a');a.className='chip-lg';a.href='course.php?c='+encodeURIComponent(c.slug);a.textContent=c.title;return a.outerHTML}).join('')}).catch(function(){});</script><section class="section dark"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">Why Empre</span><h2>A partner focused on practical outcomes</h2>
  <p>We combine technical capability with a client-first approach.</p></div>
  <div class="pillars four"><div class="pillar reveal d0"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-users"/></svg></div><h3>Client-first</h3><p>We start by understanding the business need and the people affected.</p></div><div class="pillar reveal d1"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-wrench"/></svg></div><h3>Practical expertise</h3><p>We focus on useful solutions that can be implemented and supported.</p></div><div class="pillar reveal d2"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-layers"/></svg></div><h3>Connected capabilities</h3><p>IT, security, digital delivery and training under one partner.</p></div><div class="pillar reveal d3"><div class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-refresh"/></svg></div><h3>Continuous improvement</h3><p>We value learning, documentation and sustainable outcomes.</p></div></div>
  <p class="why-tag reveal"><em>Innovation. Excellence. Integrity. Client Success.</em><br>We look forward to helping you build, secure and grow.</p>
</div></section><div class="cta-wrap"><div class="container"><div class="cta reveal">
  <div class="txt"><h2>Make technology work for your business.</h2><p>Talk to Empre Technologies about your next project, support requirement or training need.</p></div>
  <a class="btn btn-white" href="contact.php">Get in Touch <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a>
</div></div></div>
<?php include __DIR__ . '/inc/foot.php'; ?>
