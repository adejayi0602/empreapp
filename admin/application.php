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
        foreach (q("SELECT id FROM onboardings WHERE application_id=?", [$id]) as $ob) run("DELETE FROM onboarding_tasks WHERE onboarding_id=?", [$ob['id']]);
        run("DELETE FROM onboardings WHERE application_id=?", [$id]); run("DELETE FROM mail_log WHERE application_id=?", [$id]);
        run("DELETE FROM application_events WHERE application_id=?", [$id]); run("DELETE FROM application_files WHERE application_id=?", [$id]); run("DELETE FROM applications WHERE id=?", [$id]);
        log_act('application', $id, 'Deleted application ' . $a['code'] . ' (' . $a['name'] . ')'); flash('Application ' . $a['code'] . ' deleted.'); redirect('applications.php');
    }
    require_perm('careers.manage');
    $notify = post('notify', 1) === '1';
    $url = 'application.php?id=' . $id;
    $map = ['review' => 'Under Review', 'shortlist' => 'Shortlisted', 'accept' => 'Accepted', 'hire' => 'Hired', 'reject' => 'Rejected', 'not_qualified' => 'Not Qualified', 'more_info' => 'More Details Needed'];
    switch ($act) {
        case 'save_note':
            run("UPDATE applications SET admin_note=?, updated_at=? WHERE id=?", [post('admin_note', 2000), now(), $id]); log_act('application', $id, 'Internal note updated'); flash('Internal note saved.'); redirect($url);
        case 'lock':
            run("UPDATE applications SET edit_unlocked=0, updated_at=? WHERE id=?", [now(), $id]); app_event($id, 'locked', 'Editing was closed by the hiring team.', $me); flash('Editing closed for the applicant.'); redirect($url);
        case 'reset_password':
            $tmp = substr(strtr(base64_encode(random_bytes(9)), '+/', 'ab'), 0, 10);
            run("UPDATE applicants SET password_hash=? WHERE id=?", [password_hash($tmp, PASSWORD_DEFAULT), $a['applicant_id']]);
            log_act('application', $id, 'Reset the applicant portal password'); flash('New temporary password for ' . $a['email'] . ': ' . $tmp . '  (share it privately; they can change it in the portal).'); redirect($url);
        case 'interview':
            $when = str_replace('T', ' ', post('interview_at', 20));
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $when) || !strtotime($when)) { flash('Choose the interview date and time.', 'err'); redirect($url); }
            $when = date('Y-m-d H:i:s', strtotime($when)); $info = post('interview_info', 800);
            run("UPDATE applications SET interview_at=?, interview_info=?, updated_at=? WHERE id=?", [$when, $info, now(), $id]);
            $a['interview_at'] = $when; $a['interview_info'] = $info;
            if (in_array($a['status'], ['Received', 'Under Review', 'Shortlisted', 'More Details Needed', 'Interview'], true) && $a['status'] !== 'Interview') {
                $mail = hr_set_status($a, 'Interview', $note, $me, $notify);
            } else {
                app_event($id, 'status', 'Interview scheduled for ' . date('l, j F Y \a\t g:i A', strtotime($when)) . '.', $me);
                log_act('application', $id, 'Interview scheduled for ' . $when);
                $mail = $notify ? hr_notify('interview', q1("SELECT * FROM applications WHERE id=?", [$id]), $job, ['message' => $note]) : 'skipped';
            }
            flash('Interview saved for ' . date('D j M, g:i A', strtotime($when)) . '.' . hr_notice($mail)); redirect($url);
        case 'send_update':
            if (mb_strlen($note) < 5) { flash('Write the message you want to send to the applicant.', 'err'); redirect($url); }
            app_event($id, 'update', $note, $me); log_act('application', $id, 'Sent an update to the applicant');
            $mail = hr_notify('updated', q1("SELECT * FROM applications WHERE id=?", [$id]), $job, ['message' => $note]);
            flash('Update added to the applicant’s timeline.' . hr_notice($mail)); redirect($url);
        case 'start_onboarding':
            $mail = hr_set_status($a, 'Hired', $note, $me, $notify, post('start', 10) ?: null);
            flash($a['name'] . ' is hired. Their onboarding checklist is ready.' . hr_notice($mail)); redirect('onboarding-view.php?id=' . (int)qv("SELECT id FROM onboardings WHERE application_id=?", [$id]));
    }
    if (!isset($map[$act])) redirect($url);
    $set = $map[$act];
    if ($set === 'More Details Needed' && mb_strlen(trim($note)) < 5) { flash('Tell the applicant what information you need (at least a short sentence).', 'err'); redirect($url); }
    $mail = hr_set_status($a, $set, $note, $me, $notify, $set === 'Hired' ? (post('start', 10) ?: null) : null);
    flash('Application marked “' . $set . '”.' . hr_notice($mail) . ($set === 'More Details Needed' ? ' The applicant can now edit their application.' : '') . ($set === 'Hired' ? ' Onboarding checklist created.' : ''));
    redirect($set === 'Hired' ? 'onboarding-view.php?id=' . (int)qv("SELECT id FROM onboardings WHERE application_id=?", [$id]) : $url);
}
$files = q("SELECT * FROM application_files WHERE application_id=? ORDER BY id", [$id]);
$events = q("SELECT * FROM application_events WHERE application_id=? ORDER BY id DESC", [$id]);
$canManage = can('careers.manage');
$onb = q1("SELECT * FROM onboardings WHERE application_id=?", [$id]);
$mails = q("SELECT id,subject,template,status,created_at FROM mail_log WHERE application_id=? ORDER BY id DESC LIMIT 8", [$id]);
admin_head('Application ' . $a['code'], 'applications'); ?>
<p><a href="applications.php" class="muted">← All applications</a> · <a href="applications.php?job=<?= (int)$a['job_id'] ?>"><?= e($a['jtitle']) ?></a></p>
<div class="grid g21">
 <div>
  <div class="card"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start"><div><h3 style="margin:0"><?= e($a['name']) ?></h3><span class="muted small">Applied for <b><?= e($a['jtitle']) ?></b> (<?= e($a['jcompany']) ?>) on <?= e(dt($a['created_at'], 'd M Y, H:i')) ?></span></div><div><?= badge($a['status']) ?><?= (int)$a['edit_unlocked'] ? ' <span class="b b-open">editing unlocked</span>' : '' ?></div></div>
    <dl class="kv" style="margin-top:16px"><dt>Reference</dt><dd><span class="sid"><?= e($a['code']) ?></span></dd><dt>Email</dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd>
    <?php if ($a['phone']): ?><dt>Phone</dt><dd><a href="tel:<?= e($a['phone']) ?>"><?= e($a['phone']) ?></a></dd><?php endif; ?>
    <?php if ($a['linkedin']): ?><dt>LinkedIn</dt><dd><a href="<?= e($a['linkedin']) ?>" target="_blank" rel="noopener nofollow"><?= e($a['linkedin']) ?></a></dd><?php endif; ?>
    <?php if ($a['portfolio']): ?><dt>Portfolio</dt><dd><a href="<?= e($a['portfolio']) ?>" target="_blank" rel="noopener nofollow"><?= e($a['portfolio']) ?></a></dd><?php endif; ?>
    <?php if (trim((string)$a['location'])): ?><dt>Location</dt><dd><?= e($a['location']) ?></dd><?php endif; ?>
    <?php if (trim((string)$a['expected_pay'])): ?><dt>Expected pay</dt><dd><?= e($a['expected_pay']) ?></dd><?php endif; ?>
    <?php if ($a['available_from']): ?><dt>Available from</dt><dd><?= e(dt($a['available_from'])) ?></dd><?php endif; ?>
    <?php if ($a['cv_file']): ?><dt>CV</dt><dd><a class="btn sm ghost" href="application-file.php?a=<?= $id ?>&amp;f=cv">⬇ <?= e($a['cv_name']) ?></a></dd><?php endif; ?>
    <?php foreach ($files as $f): ?><dt>Document</dt><dd><a class="btn sm ghost" href="application-file.php?a=<?= $id ?>&amp;f=<?= (int)$f['id'] ?>">⬇ <?= e($f['original']) ?><?= $f['label'] && $f['label'] !== 'Added by applicant' ? ' (' . e($f['label']) . ')' : '' ?></a></dd><?php endforeach; ?>
    <?php if ($a['interview_at']): ?><dt>Interview</dt><dd><b><?= e(date('D j M Y, g:i A', strtotime($a['interview_at']))) ?></b></dd><?php endif; ?>
    <dt>Last update</dt><dd><?= e(dt($a['updated_at'], 'd M Y, H:i')) ?><?= $a['reviewed_by'] ? ' · by ' . e($a['reviewed_by']) : '' ?></dd></dl></div>
  <div class="card"><?php if (trim((string)$a['cover_letter'])): ?><h3>Cover letter</h3><div class="msg"><?= e($a['cover_letter']) ?></div><?php endif; ?>
    <?php if (trim((string)$a['jq'])): ?><h3 style="margin-top:<?= trim((string)$a['cover_letter']) ? 20 : 0 ?>px"><?= e($a['jq']) ?></h3><div class="msg"><?= e($a['answer'] ?: '—') ?></div><?php endif; ?></div>
  <div class="card"><h3>Timeline</h3><ul class="log"><?php foreach ($events as $ev): ?><li><span><?= e(dt($ev['created_at'], 'd M Y, H:i')) ?> · <?= e($ev['actor']) ?></span><?= e($ev['text']) ?></li><?php endforeach; ?></ul></div>
  <?php activity_box('application', $id); ?>
 </div>
 <div>
  <?php if ($canManage): ?>
  <div class="card"><h3>Move this applicant</h3><p class="muted small" style="margin-top:-6px">Every change is added to the applicant’s timeline and emailed to them.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="f"><label>Message to the applicant (optional for decisions, required for “More details needed”)</label><textarea name="note" placeholder="e.g. Please add two links to content you created, and your current phone number."></textarea></div>
      <label class="check-line"><input type="checkbox" name="notify" value="1" checked> Email the applicant about this change</label>
      <div class="decide">
        <span class="dh">Move forward</span>
        <button class="btn ghost" name="action" value="review">Mark under review</button>
        <button class="btn ghost" name="action" value="shortlist">★ Shortlist</button>
        <button class="btn good" name="action" value="accept">✓ Accept application</button>
        <div class="hire"><label for="start_h">Start date (optional)</label><input id="start_h" type="date" name="start"><button class="btn good" name="action" value="hire">Hire &amp; start onboarding</button></div>
        <span class="dh">Needs attention</span>
        <button class="btn warnb" name="action" value="more_info">✎ More details needed</button>
        <span class="dh">Close the application</span>
        <button class="btn danger" name="action" value="not_qualified" data-ask="Mark as Not Qualified and notify the applicant?">Not qualified</button>
        <button class="btn danger" name="action" value="reject" data-ask="Reject this application and notify the applicant?">Reject application</button>
      </div></form>
    <?php if ((int)$a['edit_unlocked']): ?><form method="post" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="lock"><button class="btn sm ghost">Close editing for the applicant</button></form><?php endif; ?>
  </div>
  <div class="card"><h3>Interview</h3><p class="muted small" style="margin-top:-6px"><?= $a['interview_at'] ? 'Scheduled. Saving again reschedules and re-sends the invitation.' : 'Pick a time. The applicant moves to “Interview” and gets the invitation.' ?></p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="interview">
      <div class="f"><label for="iw">Date and time</label><input id="iw" type="datetime-local" name="interview_at" value="<?= $a['interview_at'] ? e(date('Y-m-d\TH:i', strtotime($a['interview_at']))) : '' ?>" required></div>
      <div class="f"><label for="ii">How to join (link, address, phone)</label><textarea id="ii" name="interview_info" rows="3" placeholder="e.g. Google Meet: https://meet.google.com/…"><?= e($a['interview_info']) ?></textarea></div>
      <div class="f"><label for="in">Extra note (optional)</label><input id="in" name="note" maxlength="300"></div>
      <label class="check-line"><input type="checkbox" name="notify" value="1" checked> Email the invitation</label>
      <button class="btn dark sm"><?= $a['interview_at'] ? 'Reschedule' : 'Schedule interview' ?></button></form></div>
  <div class="card"><h3>Send an update</h3><p class="muted small" style="margin-top:-6px">Tell the applicant something without changing their status. It appears in their portal and is emailed.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="send_update"><div class="f"><textarea name="note" placeholder="e.g. We have received your portfolio and will get back to you by Friday." required></textarea></div><button class="btn dark sm">Send update</button></form></div>
  <?php if ($onb): [$od, $ot] = hr_onb_progress((int)$onb['id']); ?><div class="card"><h3>Onboarding</h3><?= hr_progress_bar($od, $ot) ?><p style="margin:12px 0 0"><a class="btn sm" href="onboarding-view.php?id=<?= (int)$onb['id'] ?>">Open checklist</a></p></div>
  <?php elseif (in_array($a['status'], ['Accepted'], true)): ?><div class="card attn"><h3>Ready to onboard</h3><p class="muted small" style="margin-top:-6px">This applicant is accepted. Mark them as hired to create their onboarding checklist.</p><a class="btn sm" href="#start_h" onclick="document.getElementById('start_h').focus();return false">Choose a start date and hire</a></div><?php endif; ?>
  <div class="card"><h3>Emails sent to the applicant</h3>
    <?php foreach ($mails as $m): ?><div class="mini-row"><span class="small"><?= e($m['subject']) ?></span><span class="muted small"><?= e(dt($m['created_at'], 'd M, H:i')) ?> · <?= e($m['status']) ?></span></div><?php endforeach; ?>
    <?php if (!$mails): ?><p class="muted small">None yet.</p><?php else: ?><a class="small" href="careers-maillog.php?app=<?= $id ?>">Open the email log →</a><?php endif; ?></div>
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
