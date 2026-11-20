<?php
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$k = (string)($_GET['k'] ?? '');
if (preg_match('/^[a-f0-9]{32}$/', $k) && hash_equals(seo_cfg('indexnow_key'), $k)) echo $k; else { http_response_code(404); echo 'Not found'; }
