<?php
declare(strict_types=1);
function doc_head(string $title): void {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . e($title) . '</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/doc.css"></head><body>
<div class="toolbar"><a href="javascript:history.back()">← Back</a><button onclick="window.print()">Print / Save as PDF</button></div><div class="sheet">';
}
function doc_company(string $kind, string $no, string $date): void {
    $s = settings();
    echo '<div class="head"><div class="co"><img src="../assets/img/logo-icon.png" alt=""><div><b>' . e($s['company_name']) . '</b>' .
        ($s['company_address'] ? '<span>' . nl2br(e($s['company_address'])) . '</span>' : '') .
        '<span>' . e($s['company_email']) . ' · ' . e($s['company_phone']) . '</span><span>' . e($s['company_web']) . '</span></div></div>
<div class="title"><h1>' . e($kind) . '</h1><span><b>' . e($no) . '</b></span><span>Date: ' . e($date) . '</span></div></div>';
}
function doc_billto(array $st): void {
    echo '<div class="grid"><div><h4>Billed to</h4><p><b>' . e($st['name']) . '</b><br>' . e($st['email']) . '<br>' . e($st['phone']) . '<br>' . e($st['location']) . '</p></div>
<div><h4>Student</h4><p>Student ID: <b>' . e($st['student_code']) . '</b><br>Course: ' . e($st['course_title']) . '</p></div></div>';
}
function doc_foot(): void { echo '<div class="foot">Thank you for choosing ' . e(setting('company_name')) . '. This is a computer-generated document.</div></div></body></html>'; }
