<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

const CONTACT_STATUSES = ['New', 'Contacted', 'Pending', 'Enrolled', 'Closed'];
const PAY_METHODS = ['Bank transfer', 'Cash', 'POS / Card', 'Online payment', 'Other'];

/* =====================================================================
 * ACCESS LEVELS  (edit ROLE_PERMS to change what each level can do)
 *   Job titles (CEO, Sales, …) are labels only. Access comes from the level.
 * ===================================================================== */
const ROLES = [
    'super_admin' => ['label' => 'Super Admin',                 'desc' => 'Full access: manage everything, delete records, assign tasks, manage staff and settings.'],
    'manager'     => ['label' => 'Level 1 · Administrator',     'desc' => 'Manage students, payments, contacts and courses. Can edit but can NOT delete anything.'],
    'billing'     => ['label' => 'Level 2 · Billing Officer',   'desc' => 'View students (read-only). Manage invoices, payments and receipts only. No creating/editing students, no deleting.'],
    'intake'      => ['label' => 'Level 3 · Intake Officer',    'desc' => 'Can only add new students. No billing and no other administration.'],
];
const ROLE_PERMS = [
    'super_admin' => ['*'],
    'manager'     => ['dashboard', 'contacts.view', 'contacts.edit', 'students.view', 'students.create', 'students.edit',
                      'courses.view', 'courses.edit', 'billing.view', 'billing.manage', 'docs.view', 'tasks.viewall'],
    'billing'     => ['dashboard', 'students.view', 'billing.view', 'billing.manage', 'docs.view'],
    'intake'      => ['students.create'],
];
/* Permissions only the Super Admin has (via '*'):
 *   contacts.delete · students.delete · courses.delete · billing.delete (delete invoice/payment)
 *   settings.manage · staff.manage · tasks.assign · services.manage (add/edit/hide/delete website services) */

/** The signed-in staff member, re-read from the database on every request so
 *  role changes and suspensions take effect immediately. */
function admin_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    $id = (int)($_SESSION['admin']['id'] ?? 0);
    if ($id) {
        $u = q1("SELECT id,name,email,title,role,active FROM admins WHERE id=?", [$id]);
        if ($u && (int)$u['active'] === 1 && isset(ROLES[$u['role']])) { $cache = $u; $_SESSION['admin']['name'] = $u['name']; }
        else unset($_SESSION['admin']);
    }
    return $cache;
}
function can(string $perm): bool {
    $u = admin_user();
    if (!$u) return false;
    $p = ROLE_PERMS[$u['role']] ?? [];
    return in_array('*', $p, true) || in_array($perm, $p, true);
}
function is_super(): bool { return (admin_user()['role'] ?? '') === 'super_admin'; }
function role_label(string $r): string { return ROLES[$r]['label'] ?? $r; }
function home_page(): string {
    return can('dashboard') ? 'index.php' : (can('students.create') ? 'student-new.php' : 'tasks.php');
}
function require_admin(): void {
    if (!qv("SELECT COUNT(*) FROM admins")) redirect('setup.php');
    if (!admin_user()) { flash('Please sign in.', 'err'); redirect('login.php'); }
}
function deny(): never {
    http_response_code(403);
    admin_head('Access denied');
    echo '<div class="card" style="max-width:640px"><h3>You do not have permission to do this</h3><p class="muted">Your access level (' . e(role_label(admin_user()['role'])) . ') does not include this action. Please contact the Super Admin if you need access.</p><a class="btn" href="' . e(home_page()) . '">Back to my dashboard</a></div>';
    admin_foot(); exit;
}
/** Use at the top of a page (view) or before a POST action (enforcement). */
function require_perm(string $perm): void { if (!can($perm)) deny(); }

function badge(string $s): string {
    return '<span class="b b-' . e(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $s))) . '">' . e($s) . '</span>';
}
function opts(array $list, string $sel = ''): string {
    $o = '';
    foreach ($list as $v) $o .= '<option' . ($v === $sel ? ' selected' : '') . '>' . e($v) . '</option>';
    return $o;
}
/** Active staff as <option>s for task assignment. */
function staff_options(?int $sel = null, string $blank = 'Not assigned'): string {
    $o = '<option value="">' . e($blank) . '</option>';
    foreach (q("SELECT id,name,title FROM admins WHERE active=1 ORDER BY name") as $a)
        $o .= '<option value="' . $a['id'] . '"' . ($sel === (int)$a['id'] ? ' selected' : '') . '>' . e($a['name'] . ($a['title'] ? ' — ' . $a['title'] : '')) . '</option>';
    return $o;
}

function admin_head(string $title, string $active = ''): void {
    $u = admin_user();
    $myOpen = (int)qv("SELECT COUNT(*) FROM tasks WHERE assigned_to=? AND status='In Progress'", [$u['id'] ?? 0]);
    $nav = [];
    if (can('dashboard'))     $nav['dashboard'] = ['index.php', 'Dashboard', 0];
    if (can('contacts.view')) $nav['contacts']  = ['contacts.php', 'Contacts', (int)qv("SELECT COUNT(*) FROM contacts WHERE status='New'")];
    if (can('students.view')) $nav['students']  = ['students.php', 'Students', (int)qv("SELECT COUNT(*) FROM students WHERE status='Applied'")];
    elseif (can('students.create')) $nav['studentnew'] = ['student-new.php', 'Add student', 0];
    if (can('courses.view'))  $nav['courses']   = ['courses.php', 'Courses', 0];
    $nav['tasks'] = ['tasks.php', can('tasks.viewall') ? 'Tasks' : 'My tasks', $myOpen];
    if (can('services.manage')) $nav['services'] = ['services.php', 'Services', 0];
    if (can('staff.manage'))  $nav['staff']     = ['staff.php', 'Staff & access', 0];
    $nav['settings'] = ['settings.php', can('settings.manage') ? 'Settings' : 'My account', 0];
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' | Empre Admin</title>
<link rel="icon" href="../assets/img/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css"></head><body>
<div class="shell"><aside class="side" id="side"><a class="brand" href="' . e(home_page()) . '"><img src="../assets/img/logo-icon.png" alt=""><span><b>Empre</b><small>ADMIN</small></span></a><nav>';
    foreach ($nav as $k => [$href, $label, $n]) {
        echo '<a href="' . $href . '" class="' . ($active === $k ? 'on' : '') . '">' . e($label) . ($n ? '<i>' . $n . '</i>' : '') . '</a>';
    }
    echo '</nav><div class="side-foot"><a href="../index.php" target="_blank">View website ↗</a><a href="logout.php">Sign out</a>' .
         ($u ? '<small><b style="color:#fff">' . e($u['name']) . '</b><br>' . e(($u['title'] ? $u['title'] . ' · ' : '') . role_label($u['role'])) . '</small>' : '') . '</div></aside>
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

/** Where to go after a task action (only allows safe internal admin pages). */
function safe_back(string $fallback): string {
    $b = post('back', 120);
    return preg_match('/^(student|task|tasks)\.php(\?[A-Za-z0-9=&_%-]*)?$/', $b) ? $b : $fallback;
}
