<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'resend') { $r = hr_resend((int)post('id')); log_act('careers', 0, 'Resent email #' . (int)post('id') . ': ' . $r); flash($r === 'sent' ? 'Email sent again.' : 'The email could not be sent from this server.', $r === 'sent' ? 'ok' : 'err'); }
    redirect('careers-maillog.php' . (isset($_GET['app']) ? '?app=' . (int)$_GET['app'] : ''));
}
$fa = (int)($_GET['app'] ?? 0); $fs = (string)($_GET['status'] ?? '');
$w = []; $p = [];
if ($fa) { $w[] = 'application_id=?'; $p[] = $fa; }
if (in_array($fs, ['sent', 'failed', 'skipped'], true)) { $w[] = 'status=?'; $p[] = $fs; }
$rows = q("SELECT * FROM mail_log" . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . " ORDER BY id DESC LIMIT 200", $p);
$c = array_column(q("SELECT status, COUNT(*) n FROM mail_log GROUP BY status"), 'n', 'status');
$names = array_column(q("SELECT k,name FROM mail_templates"), 'name', 'k');
admin_head('Email log', 'cmaillog'); ?>
<div class="tabs"><a href="?" class="<?= $fs === '' ? 'on' : '' ?>">All<i><?= array_sum($c) ?></i></a><a href="?status=sent" class="<?= $fs === 'sent' ? 'on' : '' ?>">Sent<i><?= (int)($c['sent'] ?? 0) ?></i></a><a href="?status=failed" class="<?= $fs === 'failed' ? 'on' : '' ?>">Failed<i><?= (int)($c['failed'] ?? 0) ?></i></a></div>
<?php if (!empty($c['failed'])): ?><div class="flash err" style="margin-bottom:16px">Some emails could not be handed to the mail server. Check that PHP <code>mail()</code> is enabled on your hosting, then use <b>Resend</b>. The applicant still sees every update in their portal.</div><?php endif; ?>
<div class="card"><div class="tw"><table><thead><tr><th>When</th><th>To</th><th>Email</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
 <td class="muted small"><?= e(dt($r['created_at'], 'd M Y, H:i')) ?><br><?= e($r['sent_by']) ?></td>
 <td><?= e($r['to_email']) ?><?= $r['application_id'] ? '<br><a class="small" href="application.php?id=' . (int)$r['application_id'] . '">View application</a>' : '' ?></td>
 <td><b><?= e($names[$r['template']] ?? $r['template']) ?></b><br><span class="muted small"><?= e($r['subject']) ?></span>
   <details class="mail-view"><summary>Show email</summary><iframe title="Email" srcdoc="<?= e($r['body_html']) ?>" sandbox></iframe></details></td>
 <td><span class="b <?= $r['status'] === 'sent' ? 'b-completed' : 'b-closed' ?>"><?= e($r['status']) ?></span></td>
 <td class="num"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm ghost">Resend</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No emails yet. They appear here as soon as applicants apply or their status changes.</td></tr><?php endif; ?></tbody></table></div></div>
<?php admin_foot();
