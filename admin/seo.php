<?php
require __DIR__ . '/../lib/admin_ui.php'; require_admin(); require_perm('seo.view');
const SEO_TABS = ['overview' => 'Overview', 'pages' => 'Pages', 'business' => 'Business & schema', 'technical' => 'Technical', 'tracking' => 'Tracking & verification', 'redirects' => 'Redirects & 404s', 'visibility' => 'Visibility checklist'];
$tab = isset(SEO_TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'overview';
$audit = $_SESSION['seo_audit'] ?? null;

/* ------------------------------------------------------------ actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = post('action'); $back = 'seo.php?tab=' . $tab;
    $needManage = !in_array($act, ['audit'], true);
    if ($needManage) require_perm('seo.manage');
    $url = fn(string $k, bool $slash = false) => ($v = trim(post($k, 300))) !== '' && preg_match('~^https?://[^\s<>"]+$~i', $v) ? $v : '';
    switch ($act) {
    case 'business':
        foreach (['biz_legal', 'biz_alt', 'biz_street', 'biz_city', 'biz_region', 'biz_postal', 'biz_area', 'biz_price', 'biz_founded'] as $k) seo_save($k, line(post($k, 160)));
        seo_save('biz_desc', trim(post('biz_desc', 600)));
        seo_save('biz_type', in_array(post('biz_type'), ['ProfessionalService', 'LocalBusiness', 'EducationalOrganization', 'Organization'], true) ? post('biz_type') : 'ProfessionalService');
        seo_save('biz_country', strtoupper(substr(preg_replace('/[^A-Za-z]/', '', post('biz_country', 2)), 0, 2)) ?: 'NG');
        foreach (['biz_lat' => [-90, 90], 'biz_lng' => [-180, 180]] as $k => [$lo, $hi]) { $v = trim(post($k, 20)); seo_save($k, is_numeric($v) && $v >= $lo && $v <= $hi ? $v : ''); }
        seo_save('biz_hours', trim(post('biz_hours', 400)));
        foreach (['linkedin', 'instagram', 'facebook', 'x', 'youtube', 'tiktok', 'whatsapp'] as $k) seo_save('s_' . $k, $url('s_' . $k));
        seo_save('s_other', implode("\n", array_filter(array_map(fn($l) => preg_match('~^https?://[^\s<>"]+$~i', trim($l)) ? trim($l) : '', preg_split('/\R/', post('s_other', 1200))))));
        flash('Business profile saved. Your structured data now describes the company to Google and AI assistants.'); break;
    case 'technical':
        $su = rtrim(trim(post('site_url', 200)), '/'); seo_save('site_url', preg_match('~^https?://[^/\s]+$~i', $su) ? $su : '');
        seo_save('site_name', line(post('site_name', 80)) ?: 'Empre Technologies');
        seo_save('allow_indexing', post('allow_indexing') === '1' ? '1' : '0'); seo_save('ai_crawlers', post('ai_crawlers') === '1' ? '1' : '0');
        seo_save('host_pref', in_array(post('host_pref'), ['none', 'www', 'non-www'], true) ? post('host_pref') : 'none');
        seo_save('force_https', post('force_https') === '1' ? '1' : '0'); seo_save('auto_ping', post('auto_ping') === '1' ? '1' : '0');
        seo_save('twitter', preg_replace('/[^A-Za-z0-9_]/', '', post('twitter', 40)));
        seo_save('robots_extra', trim(post('robots_extra', 2000))); seo_save('llms_extra', trim(post('llms_extra', 2000)));
        [$img, $err] = seo_save_image($_FILES['og_default'] ?? []);
        if ($err) { flash($err, 'err'); break; }
        if ($img) seo_save('og_default', $img);
        if (post('og_reset') === '1') seo_save('og_default', SEO_DEFAULTS['og_default']);
        seo_indexnow_key();
        flash('Technical settings saved.'); break;
    case 'tracking':
        foreach (['gsc', 'bing', 'yandex', 'fb_domain', 'pinterest'] as $k) {
            $raw = trim(post($k, 400)); $t = seo_clean_token($raw);
            if ($raw !== '' && $t === '') { flash('The ' . $k . ' verification code did not look right. Paste only the code, or the whole meta tag.', 'err'); redirect($back); }
            seo_save($k, $t);
        }
        foreach (['ga4' => '/^G-[A-Z0-9]{6,14}$/', 'gtm' => '/^GTM-[A-Z0-9]{4,12}$/', 'fbpixel' => '/^\d{8,20}$/', 'clarity' => '/^[a-z0-9]{8,14}$/'] as $k => $re) {
            $v = trim(post($k, 40)); if ($v !== '' && !preg_match($re, $v)) { flash('That ' . strtoupper($k) . ' ID is not in the expected format.', 'err'); redirect($back); }
            seo_save($k, $v);
        }
        seo_save('head_code', trim((string)($_POST['head_code'] ?? ''))); seo_save('body_code', trim((string)($_POST['body_code'] ?? '')));
        flash('Tracking and verification saved. Changes are live on every public page.'); break;
    case 'ping':
        [$ok, $msg] = seo_indexnow(true); flash($msg, $ok ? 'ok' : 'err'); break;
    case 'redirect_add':
        $from = '/' . ltrim(trim((string)parse_url(trim(post('from_path', 250)), PHP_URL_PATH)), '/'); $to = trim(post('to_url', 400)); $code = (int)post('code') === 302 ? 302 : 301;
        if ($from === '/' || !preg_match('~^(https?://[^\s<>"]+|/[^\s<>"]*)$~', $to)) { flash('Enter the old path (like /old-page) and where it should go (a full address or a path starting with /).', 'err'); break; }
        if (rtrim(strtolower($from), '/') === rtrim(strtolower((string)parse_url($to, PHP_URL_PATH)), '/') && !preg_match('~^https?://~', $to)) { flash('A page cannot redirect to itself.', 'err'); break; }
        run("INSERT INTO seo_redirects(from_path,to_url,code,note,created_at) VALUES(?,?,?,?,?) ON CONFLICT(from_path) DO UPDATE SET to_url=excluded.to_url,code=excluded.code,active=1", [$from, $to, $code, line(post('note', 120)), now()]);
        run("UPDATE seo_404 SET resolved=1 WHERE lower(path)=lower(?)", [$from]);
        flash('Redirect saved: ' . $from . ' → ' . $to); break;
    case 'redirect_toggle': run("UPDATE seo_redirects SET active=1-active WHERE id=?", [(int)post('id')]); flash('Redirect updated.'); break;
    case 'redirect_del': run("DELETE FROM seo_redirects WHERE id=?", [(int)post('id')]); flash('Redirect deleted.'); break;
    case '404_resolve': run("UPDATE seo_404 SET resolved=1 WHERE path=?", [post('path', 200)]); flash('Marked as handled.'); break;
    case '404_clear': run("DELETE FROM seo_404"); flash('404 log cleared.'); break;
    case 'presence':
        $valid = []; foreach (SEO_PRESENCE as [, $items]) foreach ($items as $it) $valid[$it[0]] = 1;
        foreach ($valid as $k => $_) {
            $st = in_array($_POST['st'][$k] ?? '', ['todo', 'progress', 'live'], true) ? $_POST['st'][$k] : 'todo';
            $u = trim((string)($_POST['url'][$k] ?? '')); $u = preg_match('~^https?://[^\s<>"]+$~i', $u) ? $u : '';
            run("INSERT INTO seo_presence(k,status,url,note,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(k) DO UPDATE SET status=excluded.status,url=excluded.url,note=excluded.note,updated_at=excluded.updated_at", [$k, $st, $u, line((string)($_POST['note'][$k] ?? '')), now()]);
        }
        flash('Checklist saved. Profiles marked "Live" with a link are added to your structured data automatically.'); break;
    case 'audit':
        $res = []; $pages = seo_pages_all(); $n = 0;
        foreach ($pages as $p) {
            if (++$n > 30) break;
            $u = site_url($p['path']); [$code, $html, $err, $t] = seo_fetch($u);
            $eff = seo_effective($p['key']);
            $r = ['label' => $p['label'], 'type' => $p['type'], 'url' => $u, 'code' => $code, 'time' => $t, 'err' => $err, 'issues' => [], 'stats' => []];
            if ($code === 200) { $a = seo_audit_html($html, $eff['focus']); $r['issues'] = $a['issues']; $r['stats'] = $a['stats']; }
            else $r['issues'][] = ['bad', $code ? "Page returned HTTP $code" : 'Could not load the page' . ($err ? " ($err)" : '')];
            if ($t > 2.5) $r['issues'][] = ['warn', 'Slow response: ' . round($t, 1) . ' s'];
            $res[] = $r;
        }
        foreach (['sitemap.xml' => 'Sitemap', 'robots.txt' => 'robots.txt', 'llms.txt' => 'llms.txt (AI)'] as $f => $l) { [$c] = seo_fetch(site_url($f)); $res[] = ['label' => $l, 'type' => 'File', 'url' => site_url($f), 'code' => $c, 'time' => 0, 'err' => '', 'issues' => $c === 200 ? [] : [['warn', "Returned HTTP $c. If it is 404, enable mod_rewrite or open /" . str_replace(['.xml', '.txt'], ['.php', '.php'], $f) . ' directly']], 'stats' => []]; }
        $_SESSION['seo_audit'] = ['at' => now(), 'rows' => $res]; flash('Audit finished: ' . count($res) . ' items checked.'); $back = 'seo.php?tab=overview'; break;
    }
    redirect($back);
}

/* ------------------------------------------------------------ data for tabs */
$https = !empty($_SERVER['HTTPS']) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$rows = []; foreach (q("SELECT * FROM seo_pages") as $r) $rows[$r['k']] = $r;
$plist = [];
foreach (seo_pages_all() as $p) { $eff = seo_effective($p['key']); [$sc, $iss] = seo_score_meta($eff['title'], $eff['desc'], $eff['focus'], $eff['robots'], 'x'); $p += ['eff' => $eff, 'score' => $sc, 'iss' => $iss]; $plist[] = $p; }
$avg = $plist ? (int)round(array_sum(array_column($plist, 'score')) / count($plist)) : 0;
$pres = []; foreach (q("SELECT * FROM seo_presence") as $r) $pres[$r['k']] = $r;
$presTotal = 0; $presLive = 0; foreach (SEO_PRESENCE as [, $items]) foreach ($items as $it) { $presTotal++; if (($pres[$it[0]]['status'] ?? '') === 'live') $presLive++; }
$open404 = (int)qv("SELECT COUNT(*) FROM seo_404 WHERE resolved=0");
$ogFile = __DIR__ . '/../' . ltrim(seo_cfg('og_default'), '/');
$checks = [
    [seo_indexing_allowed() ? 'good' : 'bad', 'Search engines are allowed to index the site', seo_indexing_allowed() ? '' : 'Indexing is switched OFF in Technical. Switch it on when the site is live.', 'technical'],
    [str_starts_with(seo_base(), 'https://') ? 'good' : 'warn', 'Site address uses HTTPS (' . seo_base() . ')', 'Set the live address (https://...) in Technical.', 'technical'],
    [seo_cfg('gsc') !== '' ? 'good' : 'warn', 'Google Search Console verification added', 'Verify the site in Search Console, paste the code in Tracking, then submit sitemap.xml.', 'tracking'],
    [seo_cfg('bing') !== '' ? 'good' : 'warn', 'Bing Webmaster verification added', 'Bing also powers Yahoo, DuckDuckGo and several AI assistants.', 'tracking'],
    [(seo_cfg('ga4') !== '' || seo_cfg('gtm') !== '') ? 'good' : 'warn', 'Analytics connected (GA4 or Tag Manager)', 'Add your GA4 or GTM ID so you can see traffic and leads.', 'tracking'],
    [trim(seo_cfg('biz_street') . seo_cfg('biz_city')) !== '' ? 'good' : 'warn', 'Business address in structured data (local SEO)', 'Add the office address and hours so Google can show Empre for "near me" searches.', 'business'],
    [count(seo_social_links()) >= 2 ? 'good' : 'warn', count(seo_social_links()) . ' social/profile links in structured data', 'Add every official profile on the Business tab.', 'business'],
    [is_file($ogFile) ? 'good' : 'bad', 'Default share image exists', 'Upload a 1200 x 630 image in Technical.', 'technical'],
    [seo_cfg('last_ping') !== '' ? 'good' : 'info', seo_cfg('last_ping') !== '' ? 'Search engines last notified: ' . seo_cfg('last_ping') : 'Search engines have not been notified yet', 'Press "Notify search engines now" once the site is live.', 'technical'],
    [$open404 === 0 ? 'good' : 'warn', $open404 === 0 ? 'No unhandled 404 errors' : "$open404 broken link(s) visitors hit", 'Redirect them to the right page.', 'redirects'],
    [$presLive >= 6 ? 'good' : ($presLive >= 2 ? 'warn' : 'bad'), "$presLive of $presTotal online profiles live", 'Work through the Visibility checklist.', 'visibility'],
];
$gpass = count(array_filter($checks, fn($c) => $c[0] === 'good')); $overall = (int)round($avg * 0.6 + ($gpass / count($checks) * 100) * 0.4);
$cls = fn(int $n) => $n >= 80 ? 'good' : ($n >= 50 ? 'warn' : 'bad'); $col = ['good' => '#2e9b57', 'warn' => '#D98A00', 'bad' => '#c0392b'];
$base = seo_base();

admin_head('SEO & Visibility', 'seo');
echo '<nav class="seo-tabs">'; foreach (SEO_TABS as $k => $l) echo '<a href="seo.php?tab=' . $k . '" class="' . ($tab === $k ? 'on' : '') . '">' . e($l) . ($k === 'redirects' && $open404 ? ' <i>' . $open404 . '</i>' : '') . '</a>'; echo '</nav>';
$manage = can('seo.manage');
$ro = $manage ? '' : ' disabled';
if (!$manage && !in_array($tab, ['overview', 'pages'], true)) { echo '<div class="card"><h3>Super Admin only</h3><p class="muted">Business profile, technical settings, tracking, redirects and the checklist can only be changed by the Super Admin. You can still edit page titles and descriptions in the Pages tab.</p></div>'; admin_foot(); exit; }

/* ================= OVERVIEW ================= */
if ($tab === 'overview'): ?>
<div class="grid g21" style="margin-bottom:18px">
 <div class="card" style="display:flex;gap:26px;align-items:center;flex-wrap:wrap">
  <div class="ring" style="--p:<?= $overall ?>;--c:<?= $col[$cls($overall)] ?>"><span><?= $overall ?></span></div>
  <div style="flex:1;min-width:240px"><h3 style="margin:0 0 6px">SEO health: <?= $overall >= 80 ? 'Strong' : ($overall >= 50 ? 'Good start, room to grow' : 'Needs attention') ?></h3>
   <p class="muted" style="margin:0 0 12px">Page text scores <b><?= $avg ?>/100</b> across <?= count($plist) ?> pages. <?= $gpass ?> of <?= count($checks) ?> site-wide checks pass.</p>
   <div class="actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="audit"><button class="btn">Run full site audit</button></form>
   <?php if ($manage): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="ping"><button class="btn ghost">Notify search engines now</button></form><?php endif; ?></div></div>
 </div>
 <div class="card"><h3>Your SEO files</h3><ul class="log" style="list-style:none;padding:0;margin:0">
  <li><a href="<?= e($base) ?>/sitemap.xml" target="_blank">sitemap.xml</a> <span class="muted small">· <?= count(seo_sitemap_urls()) ?> pages</span></li>
  <li><a href="<?= e($base) ?>/robots.txt" target="_blank">robots.txt</a></li>
  <li><a href="<?= e($base) ?>/llms.txt" target="_blank">llms.txt</a> <span class="muted small">· for AI assistants</span></li></ul>
  <p class="muted small" style="margin:10px 0 0">These update by themselves whenever you add or edit a service, course or job.</p></div>
</div>
<div class="grid g2">
 <div class="card"><h3>Site-wide checks</h3>
 <?php foreach ($checks as [$st, $label, $fix, $to]): ?><div class="chk"><span class="dot <?= $st ?>"></span><div style="flex:1"><b><?= e($label) ?></b><?php if ($st !== 'good'): ?><br><span class="muted small"><?= e($fix) ?> <a href="seo.php?tab=<?= $to ?>">Fix →</a></span><?php endif; ?></div></div><?php endforeach; ?></div>
 <div class="card"><h3>Pages that need the most work</h3>
 <?php $worst = $plist; usort($worst, fn($a, $b) => $a['score'] <=> $b['score']); foreach (array_slice($worst, 0, 8) as $p): ?>
  <div class="chk"><span class="dot <?= $cls($p['score']) ?>"></span><div style="flex:1"><a href="seo-page.php?k=<?= urlencode($p['key']) ?>"><b><?= e($p['label']) ?></b></a> <span class="muted small">· <?= e($p['type']) ?> · <?= $p['score'] ?>/100</span>
   <?php foreach (array_slice($p['iss'], 0, 2) as [$l, $t]): ?><br><span class="muted small"><?= e($t) ?></span><?php endforeach; ?></div></div><?php endforeach; ?>
  <p style="margin:12px 0 0"><a href="seo.php?tab=pages">See all pages →</a></p></div>
</div>
<?php if ($audit): ?><div class="card" style="margin-top:18px"><h3>Latest site audit <span class="muted small" style="font-weight:500">· <?= e(dt($audit['at'], 'd M Y, H:i')) ?></span></h3>
 <div class="tw"><table><thead><tr><th>Page</th><th>HTTP</th><th>Words</th><th>H1</th><th>Findings</th></tr></thead><tbody>
 <?php foreach ($audit['rows'] as $r): $bad = count(array_filter($r['issues'], fn($i) => $i[0] === 'bad')); $warn = count(array_filter($r['issues'], fn($i) => $i[0] === 'warn')); ?>
  <tr><td><b><?= e($r['label']) ?></b><br><span class="muted small"><?= e($r['type']) ?></span></td><td><?= $r['code'] === 200 ? badge('Paid') : badge('Closed') ?> <span class="small muted"><?= (int)$r['code'] ?></span></td>
  <td><?= $r['stats']['words'] ?? '—' ?></td><td><?= $r['stats']['h1'] ?? '—' ?></td>
  <td><?php if (!$r['issues']): ?><span class="muted">All clear ✓</span><?php endif; foreach ($r['issues'] as [$l, $t]): ?><div class="small"><span class="dot <?= $l ?>" style="display:inline-block;margin:0 6px 0 0"></span><?= e($t) ?></div><?php endforeach; ?></td></tr>
 <?php endforeach; ?></tbody></table></div></div><?php endif; ?>

<?php /* ================= PAGES ================= */ elseif ($tab === 'pages'): ?>
<div class="bar"><span class="grow muted">Every public page. Click a page to edit its Google title, description, share image and more. Service, course and job pages are written automatically and you can override any of them.</span></div>
<div class="card"><div class="tw"><table><thead><tr><th>Page</th><th>Title shown on Google</th><th>Keyword</th><th>Score</th><th></th></tr></thead><tbody>
<?php foreach ($plist as $p): $e = $p['eff']; ?>
 <tr><td><b><?= e($p['label']) ?></b><br><span class="muted small"><?= e($p['type']) ?> · /<?= e($p['path']) ?></span></td>
 <td style="max-width:360px"><?= e($e['title']) ?><br><span class="muted small"><?= e(mb_strimwidth($e['desc'], 0, 110, '…')) ?></span><?= $e['robots'] === 'noindex' ? '<br><span class="b b-closed">noindex</span>' : '' ?></td>
 <td class="small"><?= $e['focus'] !== '' ? e($e['focus']) : '<span class="muted">not set</span>' ?></td>
 <td><span class="b b-<?= ['good' => 'paid', 'warn' => 'pending', 'bad' => 'overdue'][$cls($p['score'])] ?>"><?= $p['score'] ?></span></td>
 <td class="num"><a class="btn sm ghost" href="seo-page.php?k=<?= urlencode($p['key']) ?>">Edit SEO</a></td></tr>
<?php endforeach; ?></tbody></table></div></div>

<?php /* ================= BUSINESS ================= */ elseif ($tab === 'business'): $B = seo_all(); ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="business">
<h3>Business profile (structured data)</h3><p class="muted" style="margin-top:-6px">This is what Google, Bing and AI assistants read to understand who Empre is. The more complete it is, the better your chance of a rich search result and a Google Maps presence. Use exactly the same name, address and phone number everywhere online.</p>
<div class="row"><div class="f"><label>Legal name</label><input name="biz_legal" value="<?= e($B['biz_legal']) ?>"></div><div class="f"><label>Short / brand name</label><input name="biz_alt" value="<?= e($B['biz_alt']) ?>"></div></div>
<div class="f"><label>Business description (used by Google and AI assistants)</label><textarea name="biz_desc" rows="3"><?= e($B['biz_desc']) ?></textarea></div>
<div class="row3 row"><div class="f"><label>Business type</label><select name="biz_type"><?= opts(['ProfessionalService', 'LocalBusiness', 'EducationalOrganization', 'Organization'], $B['biz_type']) ?></select></div><div class="f"><label>Year founded</label><input name="biz_founded" maxlength="4" placeholder="e.g. 2022" value="<?= e($B['biz_founded']) ?>"></div><div class="f"><label>Price range (optional)</label><input name="biz_price" placeholder="e.g. ₦₦" value="<?= e($B['biz_price']) ?>"></div></div>
<h3 style="margin-top:8px">Office location <span class="muted small" style="font-weight:500">· needed for Google Maps and "near me" searches</span></h3>
<div class="f"><label>Street address</label><input name="biz_street" value="<?= e($B['biz_street']) ?>"></div>
<div class="row3 row"><div class="f"><label>City / town</label><input name="biz_city" value="<?= e($B['biz_city']) ?>"></div><div class="f"><label>State</label><input name="biz_region" value="<?= e($B['biz_region']) ?>"></div><div class="f"><label>Postal code</label><input name="biz_postal" value="<?= e($B['biz_postal']) ?>"></div></div>
<div class="row3 row"><div class="f"><label>Country code</label><input name="biz_country" maxlength="2" value="<?= e($B['biz_country']) ?>"></div><div class="f"><label>Latitude (optional)</label><input name="biz_lat" value="<?= e($B['biz_lat']) ?>"></div><div class="f"><label>Longitude (optional)</label><input name="biz_lng" value="<?= e($B['biz_lng']) ?>"></div></div>
<div class="row"><div class="f"><label>Areas served (comma separated)</label><input name="biz_area" value="<?= e($B['biz_area']) ?>"></div><div class="f"><label>Opening hours (one per line, e.g. <code>Mo-Fr 08:00-17:00</code>)</label><textarea name="biz_hours" rows="2"><?= e($B['biz_hours']) ?></textarea></div></div>
<h3 style="margin-top:8px">Official profiles <span class="muted small" style="font-weight:500">· tells search engines these accounts belong to Empre</span></h3>
<div class="row"><?php foreach (['linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'x' => 'X (Twitter)', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp link'] as $k => $l): ?><div class="f"><label><?= $l ?></label><input name="s_<?= $k ?>" placeholder="https://" value="<?= e($B['s_' . $k]) ?>"></div><?php endforeach; ?></div>
<div class="f"><label>Other profile links (one per line: Crunchbase, Clutch, Google Business Profile...)</label><textarea name="s_other" rows="3"><?= e($B['s_other']) ?></textarea></div>
<button class="btn">Save business profile</button></form>
<div class="card"><h3>Preview of what search engines read</h3><pre style="max-height:340px;overflow:auto;background:#1f1d1d;color:#e8e2dc;padding:16px;border-radius:12px;font-size:12.5px;white-space:pre-wrap"><?= e(json_encode(seo_org(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
<a class="btn ghost sm" target="_blank" rel="noopener" href="https://search.google.com/test/rich-results?url=<?= urlencode($base . '/') ?>">Test with Google Rich Results ↗</a></div>

<?php /* ================= TECHNICAL ================= */ elseif ($tab === 'technical'): $B = seo_all(); $key = seo_indexnow_key(); ?>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="technical">
<h3>Site settings</h3>
<div class="row"><div class="f"><label>Live website address</label><input name="site_url" placeholder="https://empretechnologies.com.ng" value="<?= e($B['site_url']) ?>"><small class="muted">Used in canonical links, the sitemap and share cards. Leave empty to use the website set in Settings (<?= e($base) ?>).</small></div>
<div class="f"><label>Site name</label><input name="site_name" value="<?= e($B['site_name']) ?>"></div></div>
<div class="row"><div class="f"><label>Search engines</label><select name="allow_indexing"><option value="1"<?= $B['allow_indexing'] === '1' ? ' selected' : '' ?>>Allow indexing (recommended for the live site)</option><option value="0"<?= $B['allow_indexing'] === '0' ? ' selected' : '' ?>>Block everything (use on test copies only)</option></select></div>
<div class="f"><label>AI assistants (ChatGPT, Claude, Perplexity, Gemini)</label><select name="ai_crawlers"><option value="1"<?= $B['ai_crawlers'] === '1' ? ' selected' : '' ?>>Allow them to read the site (recommended)</option><option value="0"<?= $B['ai_crawlers'] === '0' ? ' selected' : '' ?>>Block AI crawlers</option></select></div></div>
<div class="row3 row"><div class="f"><label>Preferred address</label><select name="host_pref"><option value="none"<?= $B['host_pref'] === 'none' ? ' selected' : '' ?>>Leave as is</option><option value="www"<?= $B['host_pref'] === 'www' ? ' selected' : '' ?>>Always use www.</option><option value="non-www"<?= $B['host_pref'] === 'non-www' ? ' selected' : '' ?>>Never use www.</option></select></div>
<div class="f"><label>Force HTTPS</label><select name="force_https"><option value="0"<?= $B['force_https'] === '0' ? ' selected' : '' ?>>Off</option><option value="1"<?= $B['force_https'] === '1' ? ' selected' : '' ?>>On: redirect http:// to https://</option></select><small class="muted"><?= $https ? 'You are on HTTPS right now, so this is safe to switch on.' : 'You are NOT on HTTPS right now. Only switch on once your SSL certificate works.' ?></small></div>
<div class="f"><label>X / Twitter handle</label><input name="twitter" placeholder="empre_tech" value="<?= e($B['twitter']) ?>"></div></div>
<div class="row"><div class="f"><label>Default share image (1200 x 630)</label><input type="file" name="og_default" accept="image/png,image/jpeg,image/webp"><small class="muted">Shown when links are shared on WhatsApp, LinkedIn, Facebook and X. Current: <a href="../<?= e($B['og_default']) ?>" target="_blank"><?= e($B['og_default']) ?></a></small>
 <label style="font-weight:500;margin-top:6px"><input type="checkbox" name="og_reset" value="1" style="width:auto"> Go back to the Empre default image</label></div>
<div class="f"><label>Instant indexing (IndexNow)</label><select name="auto_ping"><option value="1"<?= $B['auto_ping'] === '1' ? ' selected' : '' ?>>Automatically notify Bing &amp; others when I change content</option><option value="0"<?= $B['auto_ping'] === '0' ? ' selected' : '' ?>>Only when I press the button</option></select></div></div>
<div class="row"><div class="f"><label>Extra robots.txt rules (advanced)</label><textarea name="robots_extra" rows="4" placeholder="Disallow: /private/"><?= e($B['robots_extra']) ?></textarea></div>
<div class="f"><label>Extra notes for AI assistants (added to llms.txt)</label><textarea name="llms_extra" rows="4" placeholder="Anything you want AI tools to know, e.g. how to get a quote."><?= e($B['llms_extra']) ?></textarea></div></div>
<button class="btn">Save technical settings</button></form>
<div class="grid g2"><div class="card"><h3>Notify search engines</h3><p class="muted">Sends every page address to Bing, Yandex and other IndexNow search engines so new content is found in minutes, not weeks. <?= $B['last_ping'] !== '' ? 'Last: <b>' . e($B['last_ping']) . '</b>' : 'Not sent yet.' ?></p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="ping"><button class="btn dark">Notify search engines now</button></form>
<p class="muted small" style="margin:12px 0 0">Google does not use IndexNow. For Google, verify the site in Search Console once and submit <code><?= e($base) ?>/sitemap.xml</code>. It then re-reads the sitemap on its own.</p></div>
<div class="card"><h3>Your generated files</h3><ul class="log" style="list-style:none;padding:0;margin:0"><li><a href="<?= e($base) ?>/sitemap.xml" target="_blank">/sitemap.xml</a></li><li><a href="<?= e($base) ?>/robots.txt" target="_blank">/robots.txt</a></li><li><a href="<?= e($base) ?>/llms.txt" target="_blank">/llms.txt</a></li><li class="muted small">IndexNow key file: /<?= e($key) ?>.txt</li></ul>
<p class="muted small" style="margin:10px 0 0">If /sitemap.xml shows "not found", your host has no URL rewriting. Use <b>/sitemap.php</b> and <b>/robots.php</b> instead, or ask the host to enable mod_rewrite.</p></div></div>

<?php /* ================= TRACKING ================= */ elseif ($tab === 'tracking'): $B = seo_all(); ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="tracking">
<h3>Search engine verification</h3><p class="muted" style="margin-top:-6px">Paste the code each service gives you. You can paste the whole <code>&lt;meta&gt;</code> tag. They prove you own the site so you can see search data and fix problems.</p>
<div class="row"><div class="f"><label>Google Search Console</label><input name="gsc" value="<?= e($B['gsc']) ?>"></div><div class="f"><label>Bing Webmaster Tools</label><input name="bing" value="<?= e($B['bing']) ?>"></div></div>
<div class="row3 row"><div class="f"><label>Yandex</label><input name="yandex" value="<?= e($B['yandex']) ?>"></div><div class="f"><label>Facebook domain verification</label><input name="fb_domain" value="<?= e($B['fb_domain']) ?>"></div><div class="f"><label>Pinterest</label><input name="pinterest" value="<?= e($B['pinterest']) ?>"></div></div>
<h3 style="margin-top:8px">Analytics &amp; advertising</h3>
<div class="row"><div class="f"><label>Google Analytics 4 ID</label><input name="ga4" placeholder="G-XXXXXXXXXX" value="<?= e($B['ga4']) ?>"></div><div class="f"><label>Google Tag Manager ID</label><input name="gtm" placeholder="GTM-XXXXXXX" value="<?= e($B['gtm']) ?>"><small class="muted">If you use Tag Manager, put GA4 inside it and leave the GA4 box empty to avoid double counting.</small></div></div>
<div class="row"><div class="f"><label>Meta (Facebook) Pixel ID</label><input name="fbpixel" value="<?= e($B['fbpixel']) ?>"></div><div class="f"><label>Microsoft Clarity ID (free heatmaps)</label><input name="clarity" value="<?= e($B['clarity']) ?>"></div></div>
<h3 style="margin-top:8px">Custom code <span class="muted small" style="font-weight:500">· advanced, Super Admin only</span></h3>
<div class="row"><div class="f"><label>Added inside &lt;head&gt; on every public page</label><textarea name="head_code" rows="4" style="font-family:monospace;font-size:13px"><?= e($B['head_code']) ?></textarea></div><div class="f"><label>Added before &lt;/body&gt;</label><textarea name="body_code" rows="4" style="font-family:monospace;font-size:13px"><?= e($B['body_code']) ?></textarea></div></div>
<p class="muted small">Only paste code from a source you trust. It runs on every visitor's browser.</p>
<button class="btn">Save tracking &amp; verification</button></form>

<?php /* ================= REDIRECTS ================= */ elseif ($tab === 'redirects'): $reds = q("SELECT * FROM seo_redirects ORDER BY id DESC"); $n404 = q("SELECT * FROM seo_404 WHERE resolved=0 ORDER BY hits DESC, last_seen DESC LIMIT 60"); ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="redirect_add"><h3>Add a redirect</h3><p class="muted" style="margin-top:-6px">Use when a page moves or a link is wrong. Visitors and Google are sent to the new place and the old page's ranking passes along (301).</p>
<div class="row3 row" style="grid-template-columns:1fr 1.3fr 130px"><div class="f"><label>Old path</label><input name="from_path" placeholder="/old-page" required></div><div class="f"><label>Send to</label><input name="to_url" placeholder="/services.php or https://..." required></div><div class="f"><label>Type</label><select name="code"><option value="301">301 permanent</option><option value="302">302 temporary</option></select></div></div>
<div class="f"><label>Note (optional)</label><input name="note" maxlength="120"></div><button class="btn">Save redirect</button></form>
<div class="card"><h3>Active redirects (<?= count($reds) ?>)</h3><div class="tw"><table><thead><tr><th>From</th><th>To</th><th>Type</th><th>Used</th><th></th></tr></thead><tbody>
<?php foreach ($reds as $r): ?><tr><td><code><?= e($r['from_path']) ?></code></td><td class="small"><?= e($r['to_url']) ?></td><td><?= (int)$r['code'] ?></td><td><?= (int)$r['hits'] ?></td><td class="num"><div class="actions">
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="redirect_toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm ghost"><?= $r['active'] ? 'Pause' : 'Resume' ?></button></form>
<form method="post" data-confirm="Delete this redirect?"><?= csrf_field() ?><input type="hidden" name="action" value="redirect_del"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form></div></td></tr>
<?php endforeach; if (!$reds): ?><tr><td colspan="5" class="muted">No redirects yet.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="card"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><h3>Broken links visitors hit (404)</h3>
<?php if ($n404): ?><form method="post" data-confirm="Clear the whole 404 log?"><?= csrf_field() ?><input type="hidden" name="action" value="404_clear"><button class="btn sm ghost">Clear log</button></form><?php endif; ?></div>
<p class="muted" style="margin-top:-6px">Fix the ones with the most visits first by sending them to the right page.</p>
<div class="tw"><table><thead><tr><th>Address</th><th>Hits</th><th>Last seen</th><th>Fix it</th></tr></thead><tbody>
<?php foreach ($n404 as $r): ?><tr><td><code><?= e($r['path']) ?></code><?= $r['referrer'] ? '<br><span class="muted small">from ' . e(mb_strimwidth($r['referrer'], 0, 70, '…')) . '</span>' : '' ?></td><td><?= (int)$r['hits'] ?></td><td class="small"><?= e(dt($r['last_seen'], 'd M, H:i')) ?></td>
<td><form method="post" style="display:flex;gap:6px;flex-wrap:wrap"><?= csrf_field() ?><input type="hidden" name="action" value="redirect_add"><input type="hidden" name="from_path" value="<?= e(parse_url($r['path'], PHP_URL_PATH)) ?>"><input name="to_url" placeholder="/services.php" style="width:170px" required><button class="btn sm">Redirect</button></form>
<form method="post" style="margin-top:4px"><?= csrf_field() ?><input type="hidden" name="action" value="404_resolve"><input type="hidden" name="path" value="<?= e($r['path']) ?>"><button class="btn sm ghost">Ignore</button></form></td></tr>
<?php endforeach; if (!$n404): ?><tr><td colspan="4" class="muted">No broken links recorded. 🎉</td></tr><?php endif; ?></tbody></table></div></div>

<?php /* ================= VISIBILITY ================= */ elseif ($tab === 'visibility'): ?>
<div class="card" style="display:flex;gap:22px;align-items:center;flex-wrap:wrap"><div class="ring" style="--p:<?= (int)round($presLive / max(1, $presTotal) * 100) ?>"><span><?= $presLive ?>/<?= $presTotal ?></span></div>
<div style="flex:1;min-width:240px"><h3 style="margin:0 0 6px">Being everywhere online</h3><p class="muted" style="margin:0">Ranking on Google is only half of it. Customers also find companies through maps, social profiles, directories and reviews. Work down this list (each service has its own sign-up that only you can complete, because it needs your login). Mark what is live and paste the profile link: live links are added to your structured data automatically.</p></div></div>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="presence">
<?php foreach (SEO_PRESENCE as [$group, $items]): ?><div class="card"><h3><?= e($group) ?></h3>
<?php foreach ($items as [$k, $label, $why, $link]): $r = $pres[$k] ?? ['status' => 'todo', 'url' => '', 'note' => '']; ?>
 <div class="chk" style="flex-wrap:wrap;align-items:flex-start"><span class="dot <?= ['todo' => 'bad', 'progress' => 'warn', 'live' => 'good'][$r['status']] ?>"></span>
 <div style="flex:1 1 280px"><b><?= e($label) ?></b> <?= $link ? '<a class="small" href="' . e($link) . '" target="_blank" rel="noopener">Open ↗</a>' : '' ?><br><span class="muted small"><?= e($why) ?></span></div>
 <select name="st[<?= $k ?>]" style="width:140px"><option value="todo"<?= $r['status'] === 'todo' ? ' selected' : '' ?>>To do</option><option value="progress"<?= $r['status'] === 'progress' ? ' selected' : '' ?>>In progress</option><option value="live"<?= $r['status'] === 'live' ? ' selected' : '' ?>>Live</option></select>
 <input name="url[<?= $k ?>]" placeholder="Profile link https://..." value="<?= e($r['url']) ?>" style="flex:1 1 220px"><input name="note[<?= $k ?>]" placeholder="Note" value="<?= e($r['note']) ?>" style="flex:1 1 160px"></div>
<?php endforeach; ?></div><?php endforeach; ?>
<button class="btn">Save checklist</button></form>
<?php endif; admin_foot();
