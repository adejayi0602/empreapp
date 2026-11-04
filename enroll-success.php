<?php
require __DIR__ . '/lib/bootstrap.php';
$s = $_SESSION['enrolled'] ?? null;
if (!$s) redirect('training.php');
$title = 'Enrolment received | Empre Technologies'; $desc = 'Your enrolment has been received.'; $active = 'training.php';
include __DIR__ . '/inc/head.php';
?>
<section class="section"><div class="container"><div class="success">
  <div class="tickbig"><svg class="ic" viewBox="0 0 24 24"><use href="#i-check"/></svg></div>
  <h1 style="font-size:clamp(28px,4vw,42px)">Enrolment received!</h1>
  <p>Thank you, <b><?= e(explode(' ', $s['name'])[0]) ?></b>. Please keep your unique student ID safe and quote it in all communication with us.</p>
  <div class="sid" id="sid"><?= e($s['code']) ?></div>
  <dl class="recap">
    <div><dt>Name</dt><dd><?= e($s['name']) ?></dd></div>
    <div><dt>Course</dt><dd><?= e($s['course']) ?></dd></div>
    <div><dt>Age / Sex</dt><dd><?= e($s['age']) ?> / <?= e($s['sex']) ?></dd></div>
    <div><dt>Location</dt><dd><?= e($s['location']) ?></dd></div>
    <div><dt>Email</dt><dd><?= e($s['email']) ?></dd></div>
    <div><dt>Phone</dt><dd><?= e($s['phone']) ?></dd></div>
  </dl>
  <p><b>What happens next?</b> Our admin team will review your application and send your invoice and payment details to <b><?= e($s['email']) ?></b>. You can choose to pay in full or in installments.</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:26px">
    <button class="btn btn-dark" type="button" onclick="window.print()">Print this page</button>
    <a class="btn btn-primary" href="training.php">Back to courses <svg class="ic" viewBox="0 0 24 24"><use href="#i-arrow"/></svg></a>
  </div>
</div></div></section>
<?php include __DIR__ . '/inc/foot.php'; ?>
