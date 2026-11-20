<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('seo.view');
$key = (string)($_GET['k'] ?? ''); $page = null;
foreach (seo_pages_all() as $p) if ($p['key'] === $key) { $page = $p; break; }
if (!$page) { flash('That page was not found.', 'err'); redirect('seo.php?tab=pages'); }
$can = can('seo.pages'); $back = 'seo-page.php?k=' . urlencode($key);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_perm('seo.pages');
    if (post('action') === 'reset') {
        run("DELETE FROM seo_pages WHERE k=?", [$key]);
        if ($seed = seo_seed_pages()[$key] ?? null) run("INSERT INTO seo_pages(k,title,description,focus,keywords,og_image,robots,updated_at,updated_by) VALUES(?,?,?,?,?,?,?,?,?)", [$key, $seed[0], $seed[1], $seed[2], $seed[3], $seed[4], 'index', now(), 'System']);
        flash('Reset to the recommended text.'); redirect($back);
    }
    $title = line(post('title', 120)); $desc = line(post('description', 320)); $focus = line(post('focus', 80)); $kw = line(post('keywords', 300));
    $canon = trim(post('canonical', 300)); if ($canon !== '' && !preg_match('~^https?://[^\s<>"]+$~i', $canon)) { flash('The canonical address must start with https://', 'err'); redirect($back); }
    $robots = post('robots') === 'noindex' ? 'noindex' : 'index';
    $old = seo_row($key); $og = (string)($old['og_image'] ?? '');
    [$img, $err] = seo_save_image($_FILES['og_image'] ?? []);
    if ($err) { flash($err, 'err'); redirect($back); }
    if ($img) $og = $img; if (post('og_remove') === '1') $og = '';
    run("INSERT INTO seo_pages(k,title,description,focus,keywords,og_image,canonical,robots,updated_at,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?)
         ON CONFLICT(k) DO UPDATE SET title=excluded.title,description=excluded.description,focus=excluded.focus,keywords=excluded.keywords,og_image=excluded.og_image,canonical=excluded.canonical,robots=excluded.robots,updated_at=excluded.updated_at,updated_by=excluded.updated_by",
        [$key, $title, $desc, $focus, $kw, $og, $canon, $robots, now(), $_SESSION['admin']['name'] ?? '']);
    flash('SEO saved for ' . $page['label'] . '. It is live now.'); redirect($back);
}
$E = seo_effective($key); [$score, $iss] = seo_score_meta($E['title'], $E['desc'], $E['focus'], $E['robots']);
$pageUrl = site_url($page['path']); $canonUrl = $E['canonical'] ?: seo_abs($page['path']); [$dt, $dd] = seo_default_meta($key);
$imgLocal = '../' . ltrim($E['og'] !== '' ? $E['og'] : seo_cfg('og_default'), '/');
$img = seo_img_url($E['og'] !== '' ? $E['og'] : seo_cfg('og_default'));
admin_head('SEO: ' . $page['label'], 'seo'); $dis = $can ? '' : ' disabled'; ?>
<div class="bar"><a class="btn ghost sm" href="seo.php?tab=pages">← All pages</a><span class="grow"></span>
 <a class="btn ghost sm" target="_blank" href="<?= e($pageUrl) ?>">View page ↗</a>
 <a class="btn ghost sm" target="_blank" rel="noopener" href="https://search.google.com/test/rich-results?url=<?= urlencode($canonUrl) ?>">Rich results test ↗</a>
 <a class="btn ghost sm" target="_blank" rel="noopener" href="https://developers.facebook.com/tools/debug/?q=<?= urlencode($canonUrl) ?>">Share preview ↗</a></div>
<div class="grid g21"><form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
 <h3><?= e($page['label']) ?> <span class="muted small" style="font-weight:500">· <?= e($page['type']) ?> · /<?= e($page['path']) ?></span></h3>
 <div class="f"><label>Page title (the blue link on Google) <span class="cnt" id="c-t"></span></label><input id="f-t" name="title" maxlength="120" value="<?= e($E['title']) ?>"<?= $dis ?>><small class="muted">Aim for 30 to 60 characters. Put the main keyword near the start and the brand at the end.</small></div>
 <div class="f"><label>Meta description (the text under it) <span class="cnt" id="c-d"></span></label><textarea id="f-d" name="description" rows="3" maxlength="320"<?= $dis ?>><?= e($E['desc']) ?></textarea><small class="muted">Aim for 90 to 160 characters. Say what the page offers and invite the click.</small></div>
 <div class="row"><div class="f"><label>Focus keyword</label><input id="f-k" name="focus" maxlength="80" placeholder="e.g. IT support Nigeria" value="<?= e($E['focus']) ?>"<?= $dis ?>><small class="muted">The one phrase this page should rank for.</small></div>
 <div class="f"><label>Other keywords (comma separated)</label><input name="keywords" maxlength="300" value="<?= e($E['keywords']) ?>"<?= $dis ?>></div></div>
 <div class="row"><div class="f"><label>Share image (1200 x 630)</label><input type="file" name="og_image" accept="image/png,image/jpeg,image/webp"<?= $dis ?>><label style="font-weight:500;margin-top:6px"><input type="checkbox" name="og_remove" value="1" style="width:auto"<?= $dis ?>> Use the default share image</label></div>
 <div class="f"><label>Visibility</label><select name="robots"<?= $dis ?>><option value="index"<?= $E['robots'] === 'index' ? ' selected' : '' ?>>Show in search results</option><option value="noindex"<?= $E['robots'] === 'noindex' ? ' selected' : '' ?>>Hide from search engines (noindex)</option></select></div></div>
 <div class="f"><label>Canonical address (advanced)</label><input name="canonical" placeholder="Leave empty unless this page duplicates another" value="<?= e($E['canonical']) ?>"<?= $dis ?>></div>
 <?php if ($can): ?><div class="actions"><button class="btn">Save SEO</button><?php if ($E['custom'] && $key !== ''): ?><button class="btn ghost" name="action" value="reset" formnovalidate onclick="return confirm('Reset this page to the recommended text?')">Reset to recommended</button><?php endif; ?></div>
 <?php else: ?><p class="muted">You can view but not edit SEO on this page.</p><?php endif; ?></form>
 <div><div class="card"><h3>Google preview</h3><div class="serp"><div class="u"><?= e(preg_replace('~^https?://~', '', $canonUrl)) ?></div><div class="t" id="p-t"></div><div class="d" id="p-d"></div></div>
 <p style="margin:14px 0 4px"><b>Score <?= $score ?>/100</b></p><?php foreach ($iss as [$l, $t]): ?><div class="chk" style="padding:6px 0"><span class="dot <?= $l ?>"></span><span class="small"><?= e($t) ?></span></div><?php endforeach; if (!$iss) echo '<p class="muted small">Everything looks good. ✓</p>'; ?></div>
 <div class="card"><h3>WhatsApp / LinkedIn / Facebook preview</h3><div class="social"><img src="<?= e($imgLocal) ?>" alt=""><div><small class="muted"><?= e(parse_url(seo_base(), PHP_URL_HOST)) ?></small><br><b id="s-t"></b></div></div>
 <?php if (!$E['custom']): ?><p class="muted small" style="margin:12px 0 0">Using the automatic text: <i><?= e($dt) ?></i>. Save to customise.</p><?php endif; ?></div></div></div>
<script>(function(){var t=document.getElementById('f-t'),d=document.getElementById('f-d');
function cnt(el,id,lo,hi){var n=el.value.length,c=document.getElementById(id);c.textContent=n+' characters';c.className='cnt '+(n>=lo&&n<=hi?'ok':'no')}
function up(){document.getElementById('p-t').textContent=t.value;document.getElementById('p-d').textContent=d.value;document.getElementById('s-t').textContent=t.value;cnt(t,'c-t',30,60);cnt(d,'c-d',90,160)}
t.addEventListener('input',up);d.addEventListener('input',up);up()})();</script>
<?php admin_foot();
