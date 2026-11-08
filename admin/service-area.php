<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('services.manage');
$id = (int)($_GET['id'] ?? 0);
$a = $id ? q1("SELECT * FROM service_areas WHERE id=?", [$id]) : null;
if ($id && !$a) { flash('Service area not found.', 'err'); redirect('services.php'); }
$f = $a ?: ['title' => '', 'tagline' => '', 'summary' => '', 'outcome' => '', 'icon' => 'spark', 'active' => 1, 'sort' => (int)qv("SELECT COALESCE(MAX(sort),0)+1 FROM service_areas")];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete' && $a) {
        if ((int)qv("SELECT COUNT(*) FROM services WHERE area_id=?", [$id])) { flash('This area still has services. Delete or move them first, or hide the area instead.', 'err'); redirect("service-area.php?id=$id"); }
        run("DELETE FROM service_areas WHERE id=?", [$id]); log_act('service_area', $id, 'Deleted area "' . $a['title'] . '"'); flash('Service area deleted.'); redirect('services.php');
    }
    $f = ['title' => line(post('title', 120)), 'tagline' => line(post('tagline', 250)), 'summary' => line(post('summary', 400)), 'outcome' => line(post('outcome', 400)),
          'icon' => svc_icon(post('icon')), 'active' => isset($_POST['active']) ? 1 : 0, 'sort' => (int)post('sort', 5)];
    if (mb_strlen($f['title']) < 3) $err = 'Area title is required.';
    else {
        if ($a) {
            run("UPDATE service_areas SET title=?,tagline=?,summary=?,outcome=?,icon=?,active=?,sort=? WHERE id=?", [$f['title'], $f['tagline'], $f['summary'], $f['outcome'], $f['icon'], $f['active'], $f['sort'], $id]);
            log_act('service_area', $id, 'Edited area'); flash('Service area saved.');
        } else {
            $nid = run("INSERT INTO service_areas(slug,title,tagline,summary,outcome,icon,active,sort,created_at) VALUES(?,?,?,?,?,?,?,?,?)",
                [unique_slug('service_areas', $f['title']), $f['title'], $f['tagline'], $f['summary'], $f['outcome'], $f['icon'], $f['active'], $f['sort'], now()]);
            log_act('service_area', $nid, 'Added area'); flash('Service area added. Now add services inside it.'); redirect('service-edit.php?area=' . $nid);
        }
        redirect('services.php');
    }
}
admin_head($a ? 'Edit service area' : 'Add service area', 'services'); ?>
<p><a href="services.php" class="muted">← All services</a></p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
  <p class="muted small" style="margin-top:0">A service area is a headline group on the website, such as “IT, Cloud &amp; Cybersecurity”. Individual services sit inside it.</p>
  <div class="row"><div class="f"><label>Area title *</label><input name="title" required value="<?= e($f['title']) ?>"></div>
  <div class="f"><label>Icon</label><select name="icon"><?php foreach (SERVICE_ICONS as $k => $n): ?><option value="<?= $k ?>" <?= $f['icon'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></div></div>
  <div class="f"><label>Tagline (one sentence under the title)</label><input name="tagline" value="<?= e($f['tagline']) ?>"></div>
  <div class="f"><label>Summary line (shown on the home page card, e.g. “Technical support • Microsoft 365 • Security”)</label><input name="summary" value="<?= e($f['summary']) ?>"></div>
  <div class="f"><label>Outcome (what the client gets)</label><input name="outcome" value="<?= e($f['outcome']) ?>"></div>
  <div class="f"><label>Display order (smaller = earlier)</label><input type="number" name="sort" value="<?= (int)$f['sort'] ?>" style="max-width:160px"></div>
  <div class="f"><label style="display:flex;gap:10px;align-items:center;font-weight:600"><input type="checkbox" name="active" style="width:auto" <?= $f['active'] ? 'checked' : '' ?>> Show this area on the website</label></div>
  <button class="btn"><?= $a ? 'Save area' : 'Add area' ?></button> <a class="btn ghost" href="services.php">Cancel</a>
</form>
<?php if ($a): ?><form method="post" data-confirm="Delete this service area?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn danger sm">Delete area</button></form><?php endif; ?>
<?php admin_foot();
