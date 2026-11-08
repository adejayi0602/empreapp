<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('services.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)post('id'); $act = post('action');
    if ($act === 'toggle' && ($s = q1("SELECT * FROM services WHERE id=?", [$id]))) {
        run("UPDATE services SET active=?, updated_at=? WHERE id=?", [$s['active'] ? 0 : 1, now(), $id]);
        log_act('service', $id, ($s['active'] ? 'Hidden from' : 'Published on') . ' the website');
        flash($s['title'] . ($s['active'] ? ' is now hidden from the website.' : ' is now live on the website.'));
    } elseif ($act === 'toggle_area' && ($a = q1("SELECT * FROM service_areas WHERE id=?", [$id]))) {
        run("UPDATE service_areas SET active=? WHERE id=?", [$a['active'] ? 0 : 1, $id]);
        flash($a['title'] . ($a['active'] ? ' is now hidden from the website.' : ' is now live on the website.'));
    }
    redirect('services.php');
}
$areas = q("SELECT * FROM service_areas ORDER BY sort, id");
$all = q("SELECT * FROM services ORDER BY sort, id");
$by = []; foreach ($all as $s) $by[(int)$s['area_id']][] = $s;
admin_head('Services', 'services'); ?>
<div class="bar"><span class="grow muted">These are the services shown on the website (Home, Our Services, footer and the contact form). Changes go live immediately.</span>
  <a class="btn ghost" href="service-area.php">+ Add service area</a><a class="btn" href="service-edit.php">+ Add service</a></div>
<?php if (!$areas): ?><div class="card"><p class="muted">No service areas yet. Add an area first, then add services inside it.</p></div><?php endif; ?>
<?php foreach ($areas as $a): $rows = $by[(int)$a['id']] ?? []; ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px">
    <div><h3 style="margin:0 0 4px"><?= e($a['title']) ?> <?= $a['active'] ? '' : '<span class="b b-closed">Hidden</span>' ?></h3><span class="muted small"><?= e($a['tagline']) ?></span></div>
    <div class="actions"><a class="btn sm ghost" href="service-area.php?id=<?= $a['id'] ?>">Edit area</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_area"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn sm ghost"><?= $a['active'] ? 'Hide area' : 'Publish area' ?></button></form></div>
  </div>
  <div class="tw"><table><thead><tr><th>Order</th><th>Service</th><th>Link</th><th>Website</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $s): ?><tr><td class="muted"><?= (int)$s['sort'] ?></td>
    <td><b><?= e($s['title']) ?></b><br><span class="muted small"><?= e($s['short']) ?></span></td>
    <td class="small muted">/services.php#<?= e($s['slug']) ?></td>
    <td><?= $s['active'] ? badge('Enrolled') . ' <span class="small muted">Live</span>' : badge('Closed') . ' <span class="small muted">Hidden</span>' ?></td>
    <td class="num"><div class="actions"><a class="btn sm ghost" href="service-edit.php?id=<?= $s['id'] ?>">Edit</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn sm ghost"><?= $s['active'] ? 'Hide' : 'Publish' ?></button></form></div></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No services in this area yet.</td></tr><?php endif; ?></tbody></table></div>
</div>
<?php endforeach; ?>
<?php admin_foot();
