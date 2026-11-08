<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('services.manage');
$id = (int)($_GET['id'] ?? 0);
$s = $id ? q1("SELECT * FROM services WHERE id=?", [$id]) : null;
if ($id && !$s) { flash('Service not found.', 'err'); redirect('services.php'); }
$areas = q("SELECT id,title FROM service_areas ORDER BY sort, id");
if (!$areas) { flash('Add a service area first.', 'err'); redirect('service-area.php'); }
$f = $s ?: ['area_id' => (int)($_GET['area'] ?? $areas[0]['id']), 'title' => '', 'icon' => 'spark', 'short' => '', 'description' => '', 'points' => '', 'cta_label' => '', 'cta_url' => '',
            'active' => 1, 'sort' => (int)qv("SELECT COALESCE(MAX(sort),0)+1 FROM services")];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete' && $s) {
        run("DELETE FROM services WHERE id=?", [$id]); log_act('service', $id, 'Deleted service "' . $s['title'] . '"');
        flash('Service "' . $s['title'] . '" was deleted and removed from the website.'); redirect('services.php');
    }
    $cta = trim(post('cta_url', 200));
    if ($cta !== '' && !preg_match('~^(https?://|[A-Za-z0-9_./?=&#%-]+$)~', $cta)) $cta = '';
    if (preg_match('~^(javascript|data):~i', $cta)) $cta = '';
    $f = ['area_id' => (int)post('area_id'), 'title' => line(post('title', 120)), 'icon' => svc_icon(post('icon')), 'short' => post('short', 300), 'description' => post('description', 4000),
          'points' => post('points', 3000), 'cta_label' => line(post('cta_label', 60)), 'cta_url' => $cta, 'active' => isset($_POST['active']) ? 1 : 0, 'sort' => (int)post('sort', 5)];
    if (mb_strlen($f['title']) < 3) $err = 'Service title is required.';
    elseif (!q1("SELECT 1 FROM service_areas WHERE id=?", [$f['area_id']])) $err = 'Choose a service area.';
    elseif ($f['short'] === '') $err = 'Add a short description for the service card.';
    else {
        if ($s) {
            run("UPDATE services SET area_id=?,title=?,icon=?,short=?,description=?,points=?,cta_label=?,cta_url=?,active=?,sort=?,updated_at=? WHERE id=?",
                [$f['area_id'], $f['title'], $f['icon'], $f['short'], $f['description'], $f['points'], $f['cta_label'], $f['cta_url'], $f['active'], $f['sort'], now(), $id]);
            log_act('service', $id, 'Edited service'); flash('Service saved. The website is updated.');
        } else {
            $nid = run("INSERT INTO services(area_id,slug,title,icon,short,description,points,cta_label,cta_url,active,sort,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$f['area_id'], unique_slug('services', $f['title']), $f['title'], $f['icon'], $f['short'], $f['description'], $f['points'], $f['cta_label'], $f['cta_url'], $f['active'], $f['sort'], now(), now()]);
            log_act('service', $nid, 'Added service'); flash('Service added' . ($f['active'] ? ' and now live on the website.' : ' (hidden).'));
        }
        redirect('services.php');
    }
}
admin_head($s ? 'Edit service' : 'Add service', 'services'); ?>
<p><a href="services.php" class="muted">← All services</a></p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
  <div class="row"><div class="f"><label>Service title *</label><input name="title" required value="<?= e($f['title']) ?>"></div>
  <div class="f"><label>Service area *</label><select name="area_id"><?php foreach ($areas as $a): ?><option value="<?= $a['id'] ?>" <?= (int)$f['area_id'] === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['title']) ?></option><?php endforeach; ?></select></div></div>
  <div class="row"><div class="f"><label>Icon</label><select name="icon"><?php foreach (SERVICE_ICONS as $k => $n): ?><option value="<?= $k ?>" <?= $f['icon'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label>Display order (smaller = earlier)</label><input type="number" name="sort" value="<?= (int)$f['sort'] ?>"></div></div>
  <div class="f"><label>Short description * (shown on the service card)</label><textarea name="short" style="min-height:70px" required><?= e($f['short']) ?></textarea></div>
  <div class="f"><label>Full description (leave a blank line between paragraphs)</label><textarea name="description" style="min-height:130px"><?= e($f['description']) ?></textarea></div>
  <div class="f"><label>What's included (one item per line)</label><textarea name="points" style="min-height:150px"><?= e($f['points']) ?></textarea></div>
  <div class="row"><div class="f"><label>Button text (optional)</label><input name="cta_label" placeholder="Default: Enquire about this" value="<?= e($f['cta_label']) ?>"></div>
  <div class="f"><label>Button link (optional)</label><input name="cta_url" placeholder="Default: our contact form. e.g. training.php" value="<?= e($f['cta_url']) ?>"></div></div>
  <div class="f"><label style="display:flex;gap:10px;align-items:center;font-weight:600"><input type="checkbox" name="active" style="width:auto" <?= $f['active'] ? 'checked' : '' ?>> Show this service on the website</label></div>
  <button class="btn"><?= $s ? 'Save service' : 'Add service' ?></button> <a class="btn ghost" href="services.php">Cancel</a>
</form>
<?php if ($s): ?><form method="post" data-confirm="Delete this service permanently? It will disappear from the website. (Use Hide if you may want it back.)"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn danger sm">Delete service</button></form><?php endif; ?>
<?php admin_foot();
