<?php
require __DIR__ . '/lib/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); unset($_SESSION['applicant']); session_regenerate_id(true); flash('You have been signed out.'); redirect('portal-login.php'); }
redirect('portal.php');
