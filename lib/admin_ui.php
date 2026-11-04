<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

const CONTACT_STATUSES = ['New', 'Contacted', 'Pending', 'Enrolled', 'Closed'];
const PAY_METHODS = ['Bank transfer', 'Cash', 'POS / Card', 'Online payment', 'Other'];

function admin_user(): ?array { return $_SESSION['admin'] ?? null; }
function require_admin(): void {
    if (!qv("SELECT COUNT(*) FROM admins")) redirect('setup.php');
    if (!admin_user()) redirect('login.php');
}
function badge(string $s): string {
    return '<span class="b b-' . e(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $s))) . '">' . e($s) . '</span>';
}
function opts(array $list, string $sel = ''): string {
    $o = '';
    foreach ($list as $v) $o .= '<option' . ($v === $sel ? ' selected' : '') . '>' . e($v) . '</option>';
    return $o;
}

function admin_head(string $title, string $active = ''): void {
    $u = admin_user();
    $newContacts = (int)qv("SELECT COUNT(*) FROM contacts WHERE status='New'");
    $newStudents = (int)qv("SELECT COUNT(*) FROM students WHERE status='Applied'");
    $nav = [
        'dashboard' => ['index.php', 'Dashboard', 0], 'contacts' => ['contacts.php', 'Contacts', $newContacts],
        'students' => ['students.php', 'Students', $newStudents], 'courses' => ['courses.php', 'Courses', 0],
        'settings' => ['settings.php', 'Settings', 0],
    ];
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' | Empre Admin</title>
<link rel="icon" href="../assets/img/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css"></head><body>
<div class="shell"><aside class="side" id="side"><a class="brand" href="index.php"><img src="../assets/img/logo-icon.png" alt=""><span><b>Empre</b><small>ADMIN</small></span></a><nav>';
    foreach ($nav as $k => [$href, $label, $n]) {
        echo '<a href="' . $href . '" class="' . ($active === $k ? 'on' : '') . '">' . e($label) . ($n ? '<i>' . $n . '</i>' : '') . '</a>';
    }
    echo '</nav><div class="side-foot"><a href="../index.html" target="_blank">View website ↗</a><a href="logout.php">Sign out</a><small>' . e($u['name'] ?? '') . '</small></div></aside>
<div class="main"><header class="top"><button class="burger" onclick="document.getElementById(\'side\').classList.toggle(\'open\')" aria-label="Menu">☰</button><h1>' . e($title) . '</h1></header><div class="content">';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) echo '<div class="flash ' . e($type) . '">' . e($msg) . '</div>';
    unset($_SESSION['flash']);
}
function admin_foot(): void { echo '</div></div></div><script>document.querySelectorAll("form[data-confirm]").forEach(function(f){f.addEventListener("submit",function(e){if(!confirm(f.dataset.confirm))e.preventDefault()})});</script></body></html>'; }

function activity_box(string $entity, int $id): void {
    $rows = q("SELECT * FROM activity WHERE entity=? AND entity_id=? ORDER BY id DESC LIMIT 30", [$entity, $id]);
    echo '<div class="card"><h3>Activity log</h3>';
    if (!$rows) echo '<p class="muted">No activity yet.</p>';
    echo '<ul class="log">';
    foreach ($rows as $r) echo '<li><span>' . e(dt($r['created_at'], 'd M Y, H:i')) . ' · ' . e($r['admin']) . '</span>' . e($r['text']) . '</li>';
    echo '</ul></div>';
}
