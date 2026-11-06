<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('contacts.view');
$id = (int)($_GET['id'] ?? 0);
$c = q1("SELECT * FROM contacts WHERE id=?", [$id]);
if (!$c) { flash('Contact not found.', 'err'); redirect('contacts.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete') { require_perm('contacts.delete'); run("DELETE FROM contacts WHERE id=?", [$id]); run("DELETE FROM activity WHERE entity='contact' AND entity_id=?", [$id]); flash('Contact deleted.'); redirect('contacts.php'); }
    require_perm('contacts.edit');
    $status = in_array(post('status'), CONTACT_STATUSES, true) ? post('status') : $c['status'];
    $fu = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('follow_up')) ? post('follow_up') : '';
    run("UPDATE contacts SET status=?, next_action=?, notes=?, follow_up=?, updated_at=? WHERE id=?", [$status, post('next_action', 500), post('notes', 5000), $fu, now(), $id]);
    $ch = [];
    if ($status !== $c['status']) $ch[] = "Status: {$c['status']} → $status";
    if (post('next_action', 500) !== (string)$c['next_action']) $ch[] = 'Next action: ' . (post('next_action', 500) ?: '(cleared)');
    if (post('notes', 5000) !== (string)$c['notes']) $ch[] = 'Notes updated';
    if ($fu !== (string)$c['follow_up']) $ch[] = 'Follow-up date: ' . ($fu ? dt($fu) : '(cleared)');
    if ($ch) log_act('contact', $id, implode(' · ', $ch));
    flash('Contact updated.'); redirect("contact.php?id=$id");
}
admin_head('Contact: ' . $c['name'], 'contacts'); ?>
<p><a href="contacts.php" class="muted">← All contacts</a></p>
<div class="grid g21">
  <div>
    <div class="card"><h3>Enquiry <?= badge($c['status']) ?></h3>
      <dl class="kv"><dt>Name</dt><dd><?= e($c['name']) ?></dd><dt>Email</dt><dd><a href="mailto:<?= e($c['email']) ?>" style="color:var(--brand-600)"><?= e($c['email']) ?></a></dd>
      <dt>Phone</dt><dd><?= $c['phone'] ? '<a href="tel:' . e($c['phone']) . '" style="color:var(--brand-600)">' . e($c['phone']) . '</a>' : '—' ?></dd>
      <dt>Interested in</dt><dd><?= e($c['service'] ?: 'General enquiry') ?></dd><dt>Received</dt><dd><?= e(dt($c['created_at'], 'd M Y, H:i')) ?></dd></dl>
      <h3 style="margin-top:20px;font-size:14px;color:var(--muted)">MESSAGE</h3><div class="msg"><?= e($c['message']) ?></div>
      <p style="margin:16px 0 0;display:flex;gap:8px;flex-wrap:wrap"><a class="btn sm" href="mailto:<?= e($c['email']) ?>?subject=<?= rawurlencode('Re: your enquiry to Empre Technologies') ?>">Reply by email</a>
      <?php if (can('students.create')): ?><a class="btn sm ghost" href="student-new.php?name=<?= urlencode($c['name']) ?>&email=<?= urlencode($c['email']) ?>&phone=<?= urlencode($c['phone']) ?>">Create student record</a><?php endif; ?></p></div>
    <?php if (!can('contacts.edit')): ?><div class="card"><h3>Engagement &amp; next steps <span class="muted small">(read-only)</span></h3><dl class="kv"><dt>Status</dt><dd><?= badge($c['status']) ?></dd><dt>Next action</dt><dd><?= e($c['next_action'] ?: '—') ?></dd><dt>Follow-up</dt><dd><?= e(dt($c['follow_up'])) ?></dd><dt>Notes</dt><dd><?= nl2br(e($c['notes'] ?: '—')) ?></dd></dl></div><?php else: ?>
    <form method="post" class="card"><?= csrf_field() ?><h3>Engagement &amp; next steps</h3>
      <div class="row"><div class="f"><label>Engagement status</label><select name="status"><?= opts(CONTACT_STATUSES, $c['status']) ?></select></div>
      <div class="f"><label>Follow-up date</label><input type="date" name="follow_up" value="<?= e($c['follow_up']) ?>"></div></div>
      <div class="f"><label>Next action (what to do next)</label><input name="next_action" maxlength="500" placeholder="e.g. Call on Monday to discuss Microsoft 365 migration quote" value="<?= e($c['next_action']) ?>"></div>
      <div class="f"><label>Admin notes</label><textarea name="notes" placeholder="Internal notes (not visible to the contact)"><?= e($c['notes']) ?></textarea></div>
      <button class="btn">Save changes</button></form><?php endif; ?>
  </div>
  <div><?php activity_box('contact', $id); ?>
    <?php if (can('contacts.delete')): ?><form method="post" class="card" data-confirm="Delete this contact permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn danger sm">Delete contact</button></form><?php endif; ?></div>
</div>
<?php admin_foot();
