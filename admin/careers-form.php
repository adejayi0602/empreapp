<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('careers.settings');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $changes = [];
    foreach (APP_FIELDS as $k => $def) {
        $m = post('f_' . $k, 10);
        if (!isset(FIELD_MODES[$m])) continue;
        if ($m !== field_mode($k)) $changes[] = $def[0] . ' → ' . FIELD_MODES[$m];
        save_setting('cf_' . $k, $m);
    }
    $min = max(0, min(1500, (int)post('cover_min', 5)));
    if ((string)$min !== (string)cover_min()) $changes[] = 'Cover letter minimum → ' . $min;
    save_setting('cf_cover_min', (string)$min);
    log_act('careers', 0, 'Application form updated' . ($changes ? ': ' . implode('; ', $changes) : ' (no changes)'));
    flash('Application form saved. New applications follow these rules straight away.'); redirect('careers-form.php');
}
admin_head('Application form', 'cform'); ?>
<div class="grid g21">
 <div>
  <form method="post" class="card"><?= csrf_field() ?>
    <h3>Choose what applicants must fill in</h3>
    <p class="muted" style="margin-top:-8px">Set each field to <b>Required</b>, <b>Optional</b> or <b>Hidden</b>. This applies to every job, to the application form and to the “update my application” form applicants see when you ask for more details.</p>
    <div class="fld-list">
      <div class="fld fixed"><div><b>Full name</b><small>Always required: it identifies the applicant.</small></div><span class="lock">Always required</span></div>
      <div class="fld fixed"><div><b>Email address</b><small>Always required: it is the applicant’s portal login and where decisions are emailed.</small></div><span class="lock">Always required</span></div>
      <?php foreach (APP_FIELDS as $k => [$label, , $hint]): $m = field_mode($k); ?>
      <div class="fld"><div><b><?= e($label) ?></b><small><?= e($hint) ?></small></div>
        <div class="seg" role="radiogroup" aria-label="<?= e($label) ?>">
        <?php foreach (FIELD_MODES as $val => $txt): ?><label class="<?= $m === $val ? 'on' : '' ?> m-<?= $val ?>"><input type="radio" name="f_<?= e($k) ?>" value="<?= $val ?>" <?= $m === $val ? 'checked' : '' ?>><?= $txt ?></label><?php endforeach; ?></div></div>
      <?php endforeach; ?>
      <div class="fld fixed"><div><b>Portal password and consent</b><small>Always required for new applicants (creates their private portal).</small></div><span class="lock">Always required</span></div>
    </div>
    <div class="f" style="max-width:280px;margin-top:18px"><label for="cover_min">Cover letter minimum length (characters)</label><input id="cover_min" name="cover_min" type="number" min="0" max="1500" value="<?= cover_min() ?>"><small class="muted">Only enforced when the cover letter is Required. Use 0 for no minimum.</small></div>
    <button class="btn">Save application form</button>
  </form>
 </div>
 <div>
  <div class="card"><h3>How it works</h3>
   <ul class="log" style="max-height:none">
    <li>Hidden fields disappear from the form and are not asked at all.</li>
    <li>Optional fields show “(optional)” and are still checked for the right format when filled in (for example a LinkedIn link).</li>
    <li>Each job can still have its own extra question (set when you edit the job).</li>
    <li>Changes apply to new applications at once. Applications already received keep what they submitted.</li>
   </ul>
   <a class="btn ghost sm" style="margin-top:12px" href="../careers.php" target="_blank">Open the careers page ↗</a></div>
 </div>
</div>
<script>document.querySelectorAll('.seg input').forEach(function(i){i.addEventListener('change',function(){i.closest('.seg').querySelectorAll('label').forEach(function(l){l.classList.toggle('on',l.contains(i))})})});</script>
<?php admin_foot();
