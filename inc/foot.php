</main>
<footer class="footer">
  <div class="container footer-main">
    <div>
      <a class="logo" href="index.php" aria-label="Empre Technologies — home">
        <img src="assets/img/logo-icon.png" alt="" width="54" height="50">
        <span class="logo-text"><b>Empre</b><small>TECHNOLOGIES</small></span>
      </a>
      <p style="margin-top:22px;max-width:340px"><b style="color:#fff">Technology that empowers businesses to grow.</b><br>A technology solutions company helping businesses and individuals with IT infrastructure, cloud, cybersecurity, software, digital design and professional training.</p>
      <div class="f-social"><a href="https://www.instagram.com/empre_technologies" aria-label="Instagram" target="_blank" rel="noopener"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-instagram"/></svg></a><a href="https://www.linkedin.com/company/empre-technologies" aria-label="LinkedIn" target="_blank" rel="noopener"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-linkedin"/></svg></a></div>
    </div>
    <div><h4>Menu</h4><ul><li><a href="index.php"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg>Home</a></li><li><a href="about.php"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg>About Us</a></li><li><a href="services.php"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg>Our Services</a></li><li><a href="training.php"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg>Training</a></li><li><a href="contact.php"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg>Contact</a></li></ul></div>
    <div><h4>Our Services</h4><ul><?php $__fs = function_exists("site_services") ? site_services() : []; foreach ($__fs as $__s): ?><li><a href="services.php#<?= e($__s['slug']) ?>"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg><?= e($__s['title']) ?></a></li><?php endforeach; ?></ul></div>
    <div><h4>Contact Us</h4>
      <ul class="contact-list">
        <li><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-mail"/></svg><a href="mailto:info@empretechnologies.com.ng">info@empretechnologies.com.ng</a></li>
        <li><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-phone"/></svg><a href="tel:+2348139645973">+234 813 964 5973</a></li>
        <li><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-globe"/></svg><a href="https://empretechnologies.com.ng" target="_blank" rel="noopener">empretechnologies.com.ng</a></li>
        <li><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-instagram"/></svg><a href="https://www.instagram.com/empre_technologies" target="_blank" rel="noopener">Instagram: @empre_technologies</a></li>
        <li><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-linkedin"/></svg><a href="https://www.linkedin.com/company/empre-technologies" target="_blank" rel="noopener">LinkedIn: Empre Technologies</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom"><div class="container">
    <span>&copy; <span data-year>2026</span> Empre Technologies Ltd. All rights reserved. &nbsp;|&nbsp; Innovation • Excellence • Integrity • Client Success</span>
    <span><a href="index.php">Home</a> &nbsp;·&nbsp; <a href="about.php">About</a> &nbsp;·&nbsp; <a href="services.php">Services</a> &nbsp;·&nbsp; <a href="training.php">Training</a> &nbsp;·&nbsp; <a href="contact.php">Contact</a></span>
  </div></div>
</footer>
<button class="to-top" aria-label="Back to top"><svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></button>
<script src="assets/js/main.js"></script>
</body>
</html>
