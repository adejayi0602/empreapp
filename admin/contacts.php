<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)post('id');
    if (post('action') === 'quick_status' && in_array(post('status'), CONTACT_STATUSES, true) && ($c = q1("SELECT * FROM contacts WHERE id=?", [$id]))) {
        run("UPDATE contacts SET status=?, updated_at=? WHERE id=?", [post('status'), now(), $id]);
        log_act('contact', $id, 'Status changed: ' . $c['status'] . ' → ' . post('status'));
        flash('Status updated for ' . $c['name'] . '.');
    }
    redirect('contacts.php?' . http_build_query(array_filter(['status' => post('f_status'), 'q' => post('f_q')])));
}
$fs = $_GET['status'] ?? ''; $fq = trim($_GET['q'] ?? '');
$where = []; $p = [];
if (in_array($fs, CONTACT_STATUSES, true)) { $where[] = 'status=?'; $p[] = $fs; }
if ($fq !== '') { $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)"; array_push($p, "%$fq%", "%$fq%", "%$fq%", "%$fq%"); }
$rows = q("SELECT * FROM contacts" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY id DESC LIMIT 300", $p);
$counts = array_column(q("SELECT status, COUNT(*) n FROM contacts GROUP BY status"), 'n', 'status');
admin_head('Contacts', 'contacts'); ?>
<div class="tabs"><a href="contacts.php" class="<?= $fs === '' ? 'on' : '' ?>">All<i><?= array_sum($counts) ?></i></a>
<?php foreach (CONTACT_STATUSES as $s): ?><a href="?status=<?= urlencode($s) ?>" class="<?= $fs === $s ? 'on' : '' ?>"><?= e($s) ?><i><?= (int)($counts[$s] ?? 0) ?></i></a><?php endforeach; ?></div>
<form class="bar" method="get"><?php if ($fs): ?><input type="hidden" name="status" value="<?= e($fs) ?>"><?php endif; ?>
<input class="grow" name="q" placeholder="Search name, email, phone or message…" value="<?= e($fq) ?>"><button class="btn dark">Search</button></form>
<div class="card"><div class="tw"><table><thead><tr><th>Date</th><th>Contact</th><th>Interested in</th><th>Status</th><th>Next action</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td class="muted small"><?= e(dt($r['created_at'])) ?></td>
<td><a href="contact.php?id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><span class="muted small"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></span></td>
<td><?= e($r['service'] ?: 'General') ?></td>
<td><form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="quick_status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="f_status" value="<?= e($fs) ?>"><input type="hidden" name="f_q" value="<?= e($fq) ?>">
<select name="status" onchange="this.form.submit()" style="width:auto;padding:6px 10px"><?= opts(CONTACT_STATUSES, $r['status']) ?></select></form></td>
<td class="small"><?= e(mb_strimwidth((string)$r['next_action'], 0, 60, '…') ?: '—') ?><?= $r['follow_up'] ? '<br><span class="muted">Due ' . e(dt($r['follow_up'])) . '</span>' : '' ?></td>
<td class="num"><a class="btn sm ghost" href="contact.php?id=<?= $r['id'] ?>">Manage</a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">No contacts found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
