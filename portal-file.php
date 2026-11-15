<?php
require __DIR__ . '/lib/bootstrap.php';
$me = require_applicant();
$a = q1("SELECT * FROM applications WHERE id=? AND applicant_id=?", [(int)($_GET['a'] ?? 0), $me['id']]);
if (!$a) { http_response_code(404); exit('Not found.'); }
$f = (string)($_GET['f'] ?? 'cv');
if ($f === 'cv') { if (!$a['cv_file']) { http_response_code(404); exit('Not found.'); } send_stored_file($a['cv_file'], $a['cv_name'] ?: 'CV'); }
$x = q1("SELECT * FROM application_files WHERE id=? AND application_id=?", [(int)$f, $a['id']]);
if (!$x) { http_response_code(404); exit('Not found.'); }
send_stored_file($x['file'], $x['original'] ?: 'document');
