<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.settings');
$tpls = q("SELECT * FROM mail_templates ORDER BY sort,k");
$keys = array_column($tpls, 'k');
$k = (string)($_GET['t'] ?? $_POST['k'] ?? ($keys[0] ?? ''));
if (!in_array($k, $keys, true)) $k = $keys[0] ?? '';
$tpl = q1("SELECT * FROM mail_templates WHERE k=?", [$k]);
$def = null; foreach (hr_default_templates() as $d) if ($d[0] === $k) $def = $d;
$placeholders = ['{first_name}', '{name}', '{job}', '{company}', '{code}', '{status}', '{message}', '{interview_when}', '{interview_info}', '{start_date}'];
$preview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = post('action', 20);
    if ($act === 'master') {
        save_setting('hr_mail_on', post('hr_mail_on', 1) === '1' ? '1' : '0');
        log_act('careers', 0, 'Automatic applicant emails turned ' . (post('hr_mail_on', 1) === '1' ? 'on' : 'off')); flash('Automatic emails ' . (post('hr_mail_on', 1) === '1' ? 'switched on' : 'switched off') . '.'); redirect('careers-emails.php?t=' . $k);
    }
    if ($tpl) {
        $form = ['subject' => line(post('subject', 200)), 'headline' => line(post('headline', 120)), 'body' => post('body', 4000), 'button' => line(post('button', 40)),
                 'link' => post('link', 10) === 'careers' ? 'careers' : 'portal', 'tone' => in_array(post('tone', 6), ['info', 'good', 'warn', 'bad'], true) ? post('tone', 6) : 'info', 'enabled' => post('enabled', 1) === '1' ? 1 : 0];
        if ($act === 'reset' && $def) {
            run("UPDATE mail_templates SET subject=?,headline=?,body=?,button=?,link=?,tone=?,enabled=1,updated_at=? WHERE k=?", [$def[2], $def[3], $def[4], $def[5], $def[6], $def[7], now(), $k]);
            log_act('careers', 0, 'Email template reset to default: ' . $tpl['name']); flash('“' . $tpl['name'] . '” was reset to the default wording.'); redirect('careers-emails.php?t=' . $k);
        }
        if ($form['subject'] === '' || $form['headline'] === '' || mb_strlen(trim($form['body'])) < 10) { flash('A template needs a subject, a headline and a message.', 'err'); $tpl = array_merge($tpl, $form); }
        elseif ($act === 'save') {
            run("UPDATE mail_templates SET subject=?,headline=?,body=?,button=?,link=?,tone=?,enabled=?,updated_at=? WHERE k=?", [$form['subject'], $form['headline'], trim($form['body']), $form['button'], $form['link'], $form['tone'], $form['enabled'], now(), $k]);
            log_act('careers', 0, 'Email template updated: ' . $tpl['name']); flash('“' . $tpl['name'] . '” saved.'); redirect('careers-emails.php?t=' . $k);
        } elseif ($act === 'preview' || $act === 'test') {
            $tpl = array_merge($tpl, $form);
            [$sa, $sj] = hr_sample(); $sa['status'] = array_search($k, hr_template_map(), true) ?: 'Under Review';
            $r = hr_render($k, $sa, $sj, ['message' => 'This is where your personal note to the applicant appears.', 'start_date' => ''], $tpl);
            $preview = $r;
            if ($act === 'test') {
                $to = (string)admin_user()['email'];
                $ok = hr_send_html($to, '[TEST] ' . $r[0], $r[1], $r[2]);
                hr_log_mail($to, '[TEST] ' . $r[0], $k, null, $ok ? 'sent' : 'failed', $r[1], $r[2]);
                flash($ok ? 'Test email sent to ' . $to . '.' : 'The test email could not be sent from this server. It is saved in the Email log.', $ok ? 'ok' : 'err');
            }
        }
    }
}
if (!$preview && $tpl) { [$sa, $sj] = hr_sample(); $sa['status'] = array_search($k, hr_template_map(), true) ?: 'Under Review'; $preview = hr_render($k, $sa, $sj, ['message' => 'This is where your personal note to the applicant appears.', 'start_date' => ''], $tpl); }
$on = hr_mail_on();
admin_head('Email templates', 'cemails'); ?>
<div class="card hr-master"><div><h3 style="margin:0">Automatic applicant emails are <?= $on ? '<span class="b b-open">ON</span>' : '<span class="b b-closed">OFF</span>' ?></h3>
  <span class="muted small">Applicants are emailed when they apply and whenever their application status changes. Each email below can be edited or switched off on its own.</span></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="master"><input type="hidden" name="k" value="<?= e($k) ?>"><input type="hidden" name="hr_mail_on" value="<?= $on ? '0' : '1' ?>"><button class="btn <?= $on ? 'ghost' : '' ?>"><?= $on ? 'Turn all off' : 'Turn all on' ?></button></form></div>
<div class="mail-layout">
  <div class="card mail-list"><h3>Emails</h3>
    <?php foreach ($tpls as $t): ?><a href="?t=<?= e($t['k']) ?>" class="<?= $t['k'] === $k ? 'on' : '' ?>"><span class="dot <?= (int)$t['enabled'] ? 'live' : '' ?>"></span><b><?= e($t['name']) ?></b><small><?= (int)$t['enabled'] ? 'On' : 'Off' ?></small></a><?php endforeach; ?>
  </div>
  <?php if ($tpl): ?>
  <form method="post" class="card mail-edit"><?= csrf_field() ?><input type="hidden" name="k" value="<?= e($k) ?>">
    <h3><?= e($tpl['name']) ?></h3>
    <label class="check-line"><input type="checkbox" name="enabled" value="1" <?= (int)$tpl['enabled'] ? 'checked' : '' ?>> Send this email automatically</label>
    <div class="f"><label for="subject">Subject</label><input id="subject" name="subject" value="<?= e($tpl['subject']) ?>" maxlength="200"></div>
    <div class="row"><div class="f"><label for="headline">Headline inside the email</label><input id="headline" name="headline" value="<?= e($tpl['headline']) ?>" maxlength="120"></div>
      <div class="f"><label for="tone">Colour of the status tag</label><select id="tone" name="tone"><?php foreach (['info' => 'Blue (information)', 'good' => 'Green (good news)', 'warn' => 'Orange (action needed)', 'bad' => 'Muted red (decline)'] as $v => $t): ?><option value="<?= $v ?>" <?= $tpl['tone'] === $v ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div></div>
    <div class="f"><label for="body">Message</label><textarea id="body" name="body" rows="11"><?= e($tpl['body']) ?></textarea>
      <small class="muted">Leave a blank line between paragraphs. Insert details with: <?php foreach ($placeholders as $p): ?><code class="ph" data-ins="<?= e($p) ?>"><?= e($p) ?></code> <?php endforeach; ?><br>A paragraph holding <code>{message}</code> or <code>{start_date}</code> disappears when there is nothing to show.</small></div>
    <div class="row"><div class="f"><label for="button">Button text <span class="muted">(blank = no button)</span></label><input id="button" name="button" value="<?= e($tpl['button']) ?>" maxlength="40"></div>
      <div class="f"><label for="link">Button opens</label><select id="link" name="link"><option value="portal" <?= $tpl['link'] === 'portal' ? 'selected' : '' ?>>The applicant’s portal</option><option value="careers" <?= $tpl['link'] === 'careers' ? 'selected' : '' ?>>The careers page</option></select></div></div>
    <div class="btnrow"><button class="btn" name="action" value="save">Save template</button><button class="btn ghost" name="action" value="preview">Preview</button><button class="btn ghost" name="action" value="test">Send test to me</button>
      <button class="btn danger" name="action" value="reset" onclick="return confirm('Reset this email to the default wording?')">Reset to default</button></div>
  </form>
  <div class="card mail-prev"><h3>Preview</h3><?php if ($preview): ?><p class="muted small" style="margin-top:-8px"><b>Subject:</b> <?= e($preview[0]) ?></p><iframe title="Email preview" srcdoc="<?= e($preview[1]) ?>" sandbox></iframe><?php endif; ?></div>
  <?php endif; ?>
</div>
<script>document.querySelectorAll('.ph').forEach(function(c){c.style.cursor='pointer';c.title='Click to insert';c.addEventListener('click',function(){var t=document.getElementById('body'),s=t.selectionStart;t.value=t.value.slice(0,s)+c.dataset.ins+t.value.slice(t.selectionEnd);t.focus();t.selectionStart=t.selectionEnd=s+c.dataset.ins.length})});</script>
<?php admin_foot();
