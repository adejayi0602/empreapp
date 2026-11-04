<?php
require __DIR__ . '/lib/bootstrap.php';
$c = course_by_slug((string)($_GET['c'] ?? ''));
if (!$c) { http_response_code(404); $title = 'Course not found | Empre Technologies'; $desc = ''; $active = 'training.php';
  include __DIR__ . '/inc/head.php';
  echo '<section class="section"><div class="container" style="text-align:center"><h1>Course not found</h1><p>This course is not available.</p><a class="btn btn-primary" href="training.php">View all courses</a></div></section>';
  include __DIR__ . '/inc/foot.php'; exit; }
$title = $c['title'] . ' Course | Empre Technologies Training';
$desc  = $c['summary'];
$active = 'training.php';
$others = q("SELECT * FROM courses WHERE active=1 AND id<>? ORDER BY sort,title LIMIT 3", [$c['id']]);
include __DIR__ . '/inc/head.php';
$ic = fn($n) => '<svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-' . e($n) . '"/></svg>';
?>
<section class="banner"><div class="container">
  <div class="crumbs"><a href="index.html">Home</a><span>/</span><a href="training.php">Training</a><span>/</span><span class="cur"><?= e($c['title']) ?></span></div>
  <h1><?= e($c['title']) ?></h1><p><?= e($c['summary']) ?></p>
</div></section>

<section class="section"><div class="container course-layout">
  <div class="course-main reveal">
    <h2>Course overview</h2>
    <?php foreach (preg_split('/\R{2,}/', trim((string)$c['description'])) as $p): ?><p><?= nl2br(e($p)) ?></p><?php endforeach; ?>
    <?php if (lines($c['outline'])): ?>
      <h2 style="margin-top:36px">What you will learn</h2>
      <ol class="curriculum" style="list-style:none;padding:0">
        <?php foreach (lines($c['outline']) as $l): ?><li><?= e($l) ?></li><?php endforeach; ?>
      </ol>
    <?php endif; ?>
    <?php if (lines($c['requirements'])): ?>
      <h2>Requirements</h2>
      <ul class="checks">
        <?php foreach (lines($c['requirements']) as $l): ?><li><span class="tick"><?= $ic('check') ?></span><span><?= e($l) ?></span></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <aside class="aside reveal d1">
    <div class="price"><small>Course fee</small><?= $c['fee'] > 0 ? e(money($c['fee'])) : 'Contact us' ?></div>
    <dl>
      <?php foreach (['Duration' => $c['duration'], 'Delivery' => $c['mode'], 'Level' => $c['level'], 'Next start' => $c['start_info']] as $k => $v): if (!$v) continue; ?>
        <div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div>
      <?php endforeach; ?>
      <div><dt>Payment</dt><dd>Full or installments</dd></div>
    </dl>
    <a class="btn btn-primary" href="enroll.php?c=<?= e($c['slug']) ?>">Enrol now <?= $ic('arrow') ?></a>
    <p class="help">Questions? <a href="contact.html">Talk to us</a></p>
  </aside>
</div></section>

<?php if ($others): ?>
<section class="section soft"><div class="container">
  <div class="sec-head center reveal"><span class="eyebrow">More courses</span><h2>You may also like</h2></div>
  <div class="cards-3">
  <?php foreach ($others as $i => $o): ?>
    <a class="card reveal d<?= $i ?>" href="course.php?c=<?= e($o['slug']) ?>"><div class="ico"><?= $ic($o['icon'] ?: 'code') ?></div><h3><?= e($o['title']) ?></h3><p><?= e($o['summary']) ?></p><span class="more">View details <?= $ic('arrow') ?></span></a>
  <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>
<?php include __DIR__ . '/inc/foot.php'; ?>
