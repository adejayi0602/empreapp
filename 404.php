<?php
require __DIR__ . '/lib/bootstrap.php';
http_response_code(404);
$path = (string)parse_url((string)($_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$p = rtrim(strtolower($path), '/') ?: '/';
if ($r = q1("SELECT * FROM seo_redirects WHERE active=1 AND (lower(from_path)=? OR lower(from_path)=?) LIMIT 1", [$p, $p . '/'])) {
    run("UPDATE seo_redirects SET hits=hits+1 WHERE id=?", [$r['id']]);
    header('Location: ' . $r['to_url'], true, (int)$r['code'] === 302 ? 302 : 301); exit;
}
seo_log_404((string)($_SERVER['REQUEST_URI'] ?? $path));
$title = 'Page not found | Empre Technologies'; $desc = 'The page you are looking for does not exist.'; $active = '';
include __DIR__ . '/inc/head.php'; ?>
<section class="section"><div class="container" style="text-align:center;max-width:680px;padding:60px 0">
  <span class="eyebrow">Error 404</span><h1 style="font-size:44px">We could not find that page</h1>
  <p>The link may be old or mistyped. Here is where most people go next:</p>
  <p style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap"><a class="btn btn-primary" href="index.php">Home</a><a class="btn btn-dark" href="services.php">Our services</a><a class="btn btn-outline-dark" href="training.php">Training</a><a class="btn btn-outline-dark" href="contact.php">Contact us</a></p>
</div></section>
<?php include __DIR__ . '/inc/foot.php'; ?>
