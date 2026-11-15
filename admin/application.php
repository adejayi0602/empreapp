<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.view');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$a = q1("SELECT a.*, j.title jtitle, j.company jcompany, j.slug jslug, j.extra_question jq FROM applications a JOIN jobs j ON j.id=a.job_id WHERE a.id=?", [$id]);
if (!$a) { flash('Application not found.', 'err'); redirect('applications.php'); }
$me = admin_user()['name'];
$job = ['title' => $a['jtitle'], 'company' => $a['jcompany']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = post('action'); $note = post('note', 1500);
    if ($act === 'delete') {
        require_perm('careers.delete');
        delete_upload($a['cv_file']);
        foreach (q("SELECT file FROM application_files WHERE application_id=?", [$id]) as $x) delete_upload($x['file']);
        run("DELETE FROM application_events WHERE application_id=?", [$id]); run("DELETE FROM application_files WHERE application_id=?", [$id]); run("DELETE FROM applications WHERE id=?", [$id]);
        log_act('application', $id, 'Deleted application ' . $a['code'] . ' (' . $a['name'] . ')'); flash('Application ' . $a['code'] . ' deleted.'); redirect('applications.php');
    }
    require_perm('careers.manage');
    $set = null; $ev = ''; $extra = ''; $unlock = 0; $mailIt = true;
    switch ($act) {
        case 'review':      $set = 'Under Review'; $ev = 'Your application is now under review.'; break;
        case 'accept':      $set = 'Accepted';     $ev = 'Your application was accepted.' . ($note !== '' ? ' ' . $note : ''); $extra = $note; break;
        case 'reject':      $set = 'Rejected';     $ev = 'Your application was not successful this time.' . ($note !== '' ? ' ' . $note : ''); $extra = $note; break;
        case 'not_qualified': $set = 'Not Qualified'; $ev = 'Your profile does not currently meet the requirements for this role.' . ($note !== '' ? ' ' . $note : ''); $extra = $note; break;
        case 'more_info':
            if (mb_strlen(trim($note)) < 5) { flash('Tell the applicant what information you need (at least a short sentence).', 'err'); redirect('application.php?id=' . $id); }
            $set = 'More Details Needed'; $unlock = 1; $ev = 'More details requested: ' . $note; $extra = "Please sign in to your portal to update your application:\n\n" . $note; break;
        case 'save_note':
            run("UPDATE applications SET admin_note=?, updated_at=? WHERE id=?", [post('admin_note', 2000), now(), $id]); log_act('application', $id, 'Internal note updated'); flash('Internal note saved.'); redirect('application.php?id=' . $id);
        case 'lock':
            run("UPDATE applications SET edit_unlocked=0, updated_at=? WHERE id=?", [now(), $id]); app_event($id, 'locked', 'Editing was closed by the hiring team.', $me); flash('Editing closed for the applicant.'); redirect('application.php?id=' . $id);
        case 'reset_password':
            $tmp = substr(strtr(base64_encode(random_bytes(9)), '+/', 'ab'), 0, 10);
            run("UPDATE applicants SET password_hash=? WHERE id=?", [password_hash($tmp, PASSWORD_DEFAULT), $a['applicant_id']]);
            log_act('application', $id, 'Reset the applicant portal password'); flash('New temporary password for ' . $a['email'] . ': ' . $tmp . '  (share it privately; they can change it in the portal).'); redirect('application.php?id=' . $id);
        default: redirect('application.php?id=' . $id);
    }
    run("UPDATE applications SET status=?, edit_unlocked=?, info_request=CASE WHEN ?='More Details Needed' THEN ? ELSE info_request END, decision_note=CASE WHEN ? IN ('Accepted','Rejected','Not Qualified') THEN ? ELSE decision_note END, reviewed_by=?, updated_at=? WHERE id=?",
        [$set, $unlock, $set, $note, $set, $note, $me, now(), $id]);
    app_event($id, 'status', $ev, $me);
    log_act('application', $id, 'Status: ' . $a['status'] . ' → ' . $set);
    [$hl] = app_status_info($set);
    mail_application_update(array_merge($a, ['status' => $set]), $job, $hl, $extra);
    flash('Application marked “' . $set . '”. The applicant has been notified' . ($set === 'More Details Needed' ? ' and can now edit their application.' : '.'));
    redirect('application.php?id=' . $id);
}
$files = q("SELECT * FROM application_files WHERE application_id=? ORDER BY id", [$id]);
$events = q("SELECT * FROM application_events WHERE application_id=? ORDER BY id DESC", [$id]);
$canManage = can('careers.manage');
admin_head('Application ' . $a['code'], 'applications'); ?>
<p><a href="applications.php" class="muted">← All applications</a> · <a href="applications.php?job=<?= (int)$a['job_id'] ?>"><?= e($a['jtitle']) ?></a></p>
<div class="grid g21">
 <div>
  <div class="card"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start"><div><h3 style="margin:0"><?= e($a['name']) ?></h3><span class="muted small">Applied for <b><?= e($a['jtitle']) ?></b> (<?= e($a['jcompany']) ?>) on <?= e(dt($a['created_at'], 'd M Y, H:i')) ?></span></div><div><?= badge($a['status']) ?><?= (int)$a['edit_unlocked'] ? ' <span class="b b-open">editing unlocked</span>' : '' ?></div></div>
    <dl class="kv" style="margin-top:16px"><dt>Reference</dt><dd><span class="sid"><?= e($a['code']) ?></span></dd><dt>Email</dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd><dt>Phone</dt><dd><a href="tel:<?= e($a['phone']) ?>"><?= e($a['phone']) ?></a></dd>
    <dt>LinkedIn</dt><dd><a href="<?= e($a['linkedin']) ?>" target="_blank" rel="noopener nofollow"><?= e($a['linkedin']) ?></a></dd>
    <dt>CV</dt><dd><a class="btn sm ghost" href="application-file.php?a=<?= $id ?>&amp;f=cv">⬇ <?= e($a['cv_name']) ?></a></dd>
    <?php foreach ($files as $f): ?><dt>Document</dt><dd><a class="btn sm ghost" href="application-file.php?a=<?= $id ?>&amp;f=<?= (int)$f['id'] ?>">⬇ <?= e($f['original']) ?></a></dd><?php endforeach; ?>
    <dt>Last update</dt><dd><?= e(dt($a['updated_at'], 'd M Y, H:i')) ?><?= $a['reviewed_by'] ? ' · by ' . e($a['reviewed_by']) : '' ?></dd></dl></div>
  <div class="card"><h3>Cover letter</h3><div class="msg"><?= e($a['cover_letter']) ?></div>
    <?php if (trim((string)$a['jq'])): ?><h3 style="margin-top:20px"><?= e($a['jq']) ?></h3><div class="msg"><?= e($a['answer'] ?: '—') ?></div><?php endif; ?></div>
  <div class="card"><h3>Timeline</h3><ul class="log"><?php foreach ($events as $ev): ?><li><span><?= e(dt($ev['created_at'], 'd M Y, H:i')) ?> · <?= e($ev['actor']) ?></span><?= e($ev['text']) ?></li><?php endforeach; ?></ul></div>
  <?php activity_box('application', $id); ?>
 </div>
 <div>
  <?php if ($canManage): ?>
  <div class="card"><h3>Decision</h3><p class="muted small" style="margin-top:-6px">The applicant is emailed and sees the new status in their portal.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="f"><label>Message to the applicant (optional for decisions, required for “More details needed”)</label><textarea name="note" placeholder="e.g. Please add two links to content you created, and your current phone number."></textarea></div>
      <div class="decide">
        <button class="btn good" name="action" value="accept">✓ Accept application</button>
        <button class="btn warnb" name="action" value="more_info">✎ More details needed</button>
        <button class="btn ghost" name="action" value="review">Mark under review</button>
        <button class="btn danger" name="action" value="not_qualified" data-ask="Mark as Not Qualified and notify the applicant?">Not qualified</button>
        <button class="btn danger" name="action" value="reject" data-ask="Reject this application and notify the applicant?">Reject application</button>
      </div></form>
    <?php if ((int)$a['edit_unlocked']): ?><form method="post" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="lock"><button class="btn sm ghost">Close editing for the applicant</button></form><?php endif; ?>
  </div>
  <div class="card"><h3>Internal note</h3><p class="muted small" style="margin-top:-6px">Only staff can see this.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="save_note"><div class="f"><textarea name="admin_note"><?= e($a['admin_note']) ?></textarea></div><button class="btn dark sm">Save note</button></form></div>
  <div class="card"><h3>Applicant account</h3><p class="muted small" style="margin-top:-6px">If the applicant forgot their portal password.</p>
    <form method="post" data-confirm="Create a new temporary password for this applicant? Their old password stops working."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="reset_password"><button class="btn sm ghost">Reset portal password</button></form></div>
  <?php else: ?><div class="card"><p class="muted">Your access level can view applications but not change their status.</p></div><?php endif; ?>
  <?php if (can('careers.delete')): ?><form method="post" data-confirm="Delete this application and its uploaded files permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="delete"><button class="btn danger sm">Delete application</button></form><?php endif; ?>
 </div>
</div>
<script>document.querySelectorAll('button[data-ask]').forEach(function(b){b.addEventListener('click',function(e){if(!confirm(b.dataset.ask))e.preventDefault()})});</script>
<?php admin_foot();
