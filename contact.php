<?php
require __DIR__ . '/lib/bootstrap.php';
$title = 'Contact Us | Empre Technologies Ltd';
$desc = 'Talk to Empre Technologies about your next project, support requirement or training need.';
$active = 'contact.php';
include __DIR__ . '/inc/head.php';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.php">Home</a><span>/</span><span class="cur">Contact</span></div>
  <h1>Contact Us</h1><p>Make technology work for your business. Talk to us about your next project, support requirement or training need.</p>
</div></section>
<section class="section"><div class="container contact-grid">
  <div class="reveal"><span class="eyebrow">Get in touch</span><h2 style="font-size:clamp(30px,3.8vw,44px);margin-top:14px">Let's talk about your business</h2>
    <p>Tell us about your next project, support requirement or training need. A member of the Empre team will respond as soon as possible. You can also reach us directly using the details below.</p>
    <div class="info-list"><a class="info" href="mailto:info@empretechnologies.com.ng"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-mail"/></svg></span><div><small>Email</small><b>info@empretechnologies.com.ng</b></div></a><a class="info" href="tel:+2348139645973"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-phone"/></svg></span><div><small>Phone</small><b>+234 813 964 5973</b></div></a><a class="info" href="https://empretechnologies.com.ng"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-globe"/></svg></span><div><small>Website</small><b>empretechnologies.com.ng</b></div></a><a class="info" href="https://www.instagram.com/empre_technologies"><span class="ico"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-instagram"/></svg></span><div><small>Instagram</small><b>@empre_technologies</b></div></a></div></div>
  <div class="form-card reveal d1">
    <h3>Send us a message</h3><p>Fields marked * are required.</p>
    <form id="contact-form" action="send-mail.php" method="post" novalidate>
      <div class="form-row">
        <div class="field"><label for="name">Full name *</label><input id="name" name="name" type="text" autocomplete="name" required><span class="err">Please enter your name.</span></div>
        <div class="field"><label for="email">Email address *</label><input id="email" name="email" type="email" autocomplete="email" required><span class="err">Please enter a valid email.</span></div>
      </div>
      <div class="form-row">
        <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" type="tel" autocomplete="tel"></div>
        <div class="field"><label for="service">Service of interest</label><select id="service" name="service"><option value="">Select a service (optional)</option><?php foreach (site_services() as $__s): ?><option value="<?= e($__s['title']) ?>" data-id="<?= e($__s['slug']) ?>"><?= e($__s['title']) ?></option><?php endforeach; ?><option value="Other">Other / Not sure</option></select></div>
      </div>
      <div class="field"><label for="message">How can we help? *</label><textarea id="message" name="message" required></textarea><span class="err">Please tell us a little more (at least 10 characters).</span></div>
      <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <button class="btn btn-primary" type="submit">Send Message <svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></button>
      <div class="form-note" role="status" aria-live="polite"></div>
    </form>
  </div>
</div></section>
<script>
(function(){var m=location.search.match(/service=([\w-]+)/);if(!m)return;var o=document.querySelector('#service option[data-id="'+m[1]+'"]');if(o)o.parentNode.value=o.value;})();
</script>

<?php include __DIR__ . '/inc/foot.php'; ?>
