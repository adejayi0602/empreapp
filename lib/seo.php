<?php
declare(strict_types=1);
/* =====================================================================
 * SEO engine. Everything the public site tells search engines, social
 * networks and AI assistants comes from here and is managed in
 * Admin > SEO & Visibility (no code changes needed).
 *   seo_pages      per-page title / description / keyword / image / robots
 *   seo_redirects  301/302 redirects managed by the Super Admin
 *   seo_404        pages visitors asked for that do not exist
 *   seo_presence   "everywhere on the internet" checklist
 *   settings       seo_* keys: site URL, verification codes, analytics, business profile...
 * ===================================================================== */

const SEO_DEFAULTS = [
    'site_url' => '', 'site_name' => 'Empre Technologies', 'allow_indexing' => '1', 'ai_crawlers' => '1',
    'robots_extra' => '', 'llms_extra' => '', 'og_default' => 'assets/img/og/default.png', 'twitter' => '', 'locale' => 'en_NG',
    'force_https' => '0', 'host_pref' => 'none',
    'gsc' => '', 'bing' => '', 'yandex' => '', 'fb_domain' => '', 'pinterest' => '',
    'ga4' => '', 'gtm' => '', 'fbpixel' => '', 'clarity' => '', 'head_code' => '', 'body_code' => '',
    'indexnow_key' => '', 'auto_ping' => '1', 'last_ping' => '',
    'biz_type' => 'ProfessionalService', 'biz_legal' => 'Empre Technologies Limited', 'biz_alt' => 'Empre', 'biz_desc' => 'Empre Technologies is a Nigerian technology solutions company providing IT support, Microsoft 365, cloud and cybersecurity, website and software development, technology consulting and practical technology training.',
    'biz_founded' => '', 'biz_street' => '', 'biz_city' => '', 'biz_region' => '', 'biz_postal' => '', 'biz_country' => 'NG',
    'biz_lat' => '', 'biz_lng' => '', 'biz_hours' => '', 'biz_area' => 'Nigeria', 'biz_price' => '', 'biz_logo' => 'assets/img/logo-full.png',
    's_linkedin' => 'https://www.linkedin.com/company/empre-technologies', 's_instagram' => 'https://www.instagram.com/empre_technologies',
    's_facebook' => '', 's_x' => '', 's_youtube' => '', 's_tiktok' => '', 's_whatsapp' => '', 's_other' => '',
];
/** Key => [label, noindex?] pages that are never offered to search engines. */
const SEO_PRIVATE_SCRIPTS = ['apply.php', 'portal.php', 'portal-app.php', 'portal-login.php', 'portal-file.php', 'portal-logout.php', 'enroll.php', 'enroll-success.php', 'send-mail.php', '404.php'];
const SEO_STATIC_PAGES = [
    'index.php'    => ['Home', 'index.php'],
    'about.php'    => ['About Us', 'about.php'],
    'services.php' => ['Our Services', 'services.php'],
    'training.php' => ['Training', 'training.php'],
    'careers.php'  => ['Careers', 'careers.php'],
    'contact.php'  => ['Contact', 'contact.php'],
];

function seo_schema(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS seo_pages(k TEXT PRIMARY KEY, title TEXT DEFAULT '', description TEXT DEFAULT '', focus TEXT DEFAULT '', keywords TEXT DEFAULT '',
        og_image TEXT DEFAULT '', canonical TEXT DEFAULT '', robots TEXT DEFAULT 'index', updated_at TEXT, updated_by TEXT);
    CREATE TABLE IF NOT EXISTS seo_redirects(id INTEGER PRIMARY KEY, from_path TEXT NOT NULL UNIQUE, to_url TEXT NOT NULL, code INTEGER DEFAULT 301, hits INTEGER DEFAULT 0, active INTEGER DEFAULT 1, note TEXT DEFAULT '', created_at TEXT);
    CREATE TABLE IF NOT EXISTS seo_404(path TEXT PRIMARY KEY, hits INTEGER DEFAULT 1, referrer TEXT DEFAULT '', first_seen TEXT, last_seen TEXT, resolved INTEGER DEFAULT 0);
    CREATE TABLE IF NOT EXISTS seo_presence(k TEXT PRIMARY KEY, status TEXT DEFAULT 'todo', url TEXT DEFAULT '', note TEXT DEFAULT '', updated_at TEXT);
    ");
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='seo_seeded'")->fetch()) {
        $ins = $pdo->prepare("INSERT OR IGNORE INTO seo_pages(k,title,description,focus,keywords,og_image,robots,updated_at,updated_by) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach (seo_seed_pages() as $k => $r) $ins->execute([$k, $r[0], $r[1], $r[2], $r[3], $r[4], 'index', date('Y-m-d H:i:s'), 'System']);
        $pdo->exec("INSERT OR IGNORE INTO settings(k,v) VALUES('seo_seeded','1')");
    }
}
/** Starting point written for the Nigerian market. Super Admin / Level 1 can refine in the SEO console. */
function seo_seed_pages(): array {
    return [
     'index.php' => ['IT Support Company in Nigeria | Empre Technologies', 'Empre Technologies is an IT support company in Nigeria offering Microsoft 365, cloud and cybersecurity, website and software development, and hands-on tech training.', 'IT support company in Nigeria', 'IT support Nigeria, Microsoft 365 Nigeria, cybersecurity Nigeria, cloud services, web development Nigeria, tech training Nigeria', 'assets/img/og/home.png'],
     'about.php' => ['About Empre | Technology Solutions Company in Nigeria', 'Empre Technologies is a technology solutions company in Nigeria delivering practical IT, cloud, cybersecurity, software and training services to businesses and individuals.', 'technology solutions company', 'about Empre Technologies, technology company Nigeria, IT solutions provider', 'assets/img/og/about.png'],
     'services.php' => ['IT Services in Nigeria: Cloud, Security & Software | Empre', 'Managed IT services in Nigeria: support, Microsoft 365, endpoint security, cloud and DevOps, websites, web apps and consulting. Get a quote today.', 'IT services', 'IT support, Microsoft 365, Intune, Defender for Endpoint, cloud infrastructure, DevOps, website design, web applications, technology consulting', 'assets/img/og/services.png'],
     'training.php' => ['Tech Training in Nigeria | Cloud, Cybersecurity, DevOps', 'Practical, career-ready tech training in front-end development, cloud computing, cybersecurity, DevOps and product design. Virtual or in person. Enrol today.', 'tech training', 'tech training Nigeria, cloud computing course, cybersecurity course, DevOps course, front-end development course, product design course', 'assets/img/og/training.png'],
     'careers.php' => ['Tech Jobs & Careers at Empre Technologies | Apply Online', 'Looking for tech jobs in Nigeria? Join Empre Technologies and Conversa AI. See open roles, apply online and track your application.', 'tech jobs', 'tech jobs Nigeria, careers Empre Technologies, Conversa AI jobs, sales jobs Nigeria, customer support jobs', 'assets/img/og/careers.png'],
     'contact.php' => ['Contact Empre Technologies | Get an IT or Training Quote', 'Contact Empre Technologies about IT support, cloud, security, software development or training. Call +234 813 964 5973 or email info@empretechnologies.com.ng.', 'contact Empre Technologies', 'contact Empre Technologies, IT quote Nigeria, tech support enquiry', 'assets/img/og/contact.png'],
    ];
}

/* ------------------------------------------------------------------ settings */
function seo_all(?array $reset = null): array {
    static $c = null;
    if ($reset !== null || $c === null) {
        $c = SEO_DEFAULTS;
        foreach (q("SELECT k,v FROM settings WHERE k LIKE 'seo\\_%' ESCAPE '\\'") as $r) {
            $k = substr($r['k'], 4);
            if (array_key_exists($k, $c)) $c[$k] = (string)$r['v'];
        }
    }
    return $c;
}
function seo_cfg(string $k): string { return seo_all()[$k] ?? ''; }
function seo_save(string $k, string $v): void { save_setting('seo_' . $k, $v); seo_all([]); }

function seo_base(): string {
    $u = trim(seo_cfg('site_url'));
    if (preg_match('~^https?://[^/\s]+$~i', rtrim($u, '/'))) return rtrim($u, '/');
    $w = trim(setting('company_web'));
    return 'https://' . preg_replace('~^https?://~i', '', rtrim($w ?: 'empretechnologies.com.ng', '/'));
}
function seo_abs(string $p): string { return preg_match('~^https?://~i', $p) ? $p : seo_base() . '/' . ltrim($p, '/'); }
function seo_indexing_allowed(): bool { return seo_cfg('allow_indexing') === '1'; }

/* ------------------------------------------------------------------ which page is this? */
function seo_script(): string { return basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php')); }
/** Key used in seo_pages and the canonical URL path for the current request. */
function seo_current(): array {
    $s = seo_script();
    foreach (['course.php' => 'c', 'job.php' => 'j', 'service.php' => 's'] as $f => $p) {
        if ($s === $f) {
            $v = preg_replace('/[^a-z0-9-]/i', '', (string)($_GET[$p] ?? ''));
            $kind = ['course.php' => 'course', 'job.php' => 'job', 'service.php' => 'service'][$f];
            return [$kind . ':' . $v, $f . '?' . $p . '=' . $v];
        }
    }
    return [$s, $s === 'index.php' ? '' : $s];
}
function seo_row(string $key): ?array { return q1("SELECT * FROM seo_pages WHERE k=?", [$key]); }

/* ------------------------------------------------------------------ head */
function seo_meta_attr(string $v): string { return e(trim(preg_replace('/\s+/', ' ', $v))); }
function seo_img_url(string $p): string { return $p === '' ? '' : seo_abs($p); }

function seo_head(string $title, string $desc, string $active = ''): void {
    [$key, $path] = seo_current();
    $row = seo_row($key);
    $is404 = http_response_code() === 404;
    $private = in_array(seo_script(), SEO_PRIVATE_SCRIPTS, true);
    if ($row && !$is404 && !$private) {
        if (trim($row['title']) !== '') $title = $row['title'];
        if (trim($row['description']) !== '') $desc = $row['description'];
    }
    $noindex = $is404 || $private || !seo_indexing_allowed() || ($row && $row['robots'] === 'noindex');
    $canon = $row && preg_match('~^https?://~i', (string)$row['canonical']) ? $row['canonical'] : seo_abs($path);
    $img = seo_img_url(($row && $row['og_image'] !== '') ? $row['og_image'] : seo_cfg('og_default'));
    $name = seo_cfg('site_name');
    $type = seo_script() === 'job.php' ? 'article' : 'website';
    $o = [];
    $o[] = '<meta charset="utf-8">';
    $o[] = '<meta name="viewport" content="width=device-width,initial-scale=1">';
    $o[] = '<title>' . e($title) . '</title>';
    $o[] = '<meta name="description" content="' . seo_meta_attr($desc) . '">';
    if ($row && trim($row['keywords']) !== '') $o[] = '<meta name="keywords" content="' . seo_meta_attr($row['keywords']) . '">';
    $o[] = '<meta name="robots" content="' . ($noindex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1') . '">';
    if (!$noindex) $o[] = '<link rel="canonical" href="' . e($canon) . '">';
    $o[] = '<meta name="author" content="' . e($name) . '">';
    $o[] = '<meta name="theme-color" content="#F77F00">';
    foreach (['gsc' => 'google-site-verification', 'bing' => 'msvalidate.01', 'yandex' => 'yandex-verification', 'fb_domain' => 'facebook-domain-verification', 'pinterest' => 'p:domain_verify'] as $k => $n)
        if (seo_cfg($k) !== '') $o[] = '<meta name="' . $n . '" content="' . e(seo_cfg($k)) . '">';
    // Open Graph (Facebook, WhatsApp, LinkedIn, Telegram) + Twitter/X
    $o[] = '<meta property="og:site_name" content="' . e($name) . '"><meta property="og:locale" content="' . e(seo_cfg('locale')) . '"><meta property="og:type" content="' . $type . '">';
    $o[] = '<meta property="og:title" content="' . seo_meta_attr($title) . '"><meta property="og:description" content="' . seo_meta_attr($desc) . '"><meta property="og:url" content="' . e($canon) . '">';
    if ($img) $o[] = '<meta property="og:image" content="' . e($img) . '"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta property="og:image:alt" content="' . seo_meta_attr($title) . '">';
    $o[] = '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . seo_meta_attr($title) . '"><meta name="twitter:description" content="' . seo_meta_attr($desc) . '">';
    if ($img) $o[] = '<meta name="twitter:image" content="' . e($img) . '">';
    if (seo_cfg('twitter') !== '') $o[] = '<meta name="twitter:site" content="@' . e(ltrim(seo_cfg('twitter'), '@')) . '">';
    if (seo_cfg('biz_country') === 'NG') $o[] = '<meta name="geo.region" content="NG">';
    if (seo_cfg('biz_lat') !== '' && seo_cfg('biz_lng') !== '') $o[] = '<meta name="geo.position" content="' . e(seo_cfg('biz_lat') . ';' . seo_cfg('biz_lng')) . '"><meta name="ICBM" content="' . e(seo_cfg('biz_lat') . ', ' . seo_cfg('biz_lng')) . '">';
    $o[] = '<link rel="icon" type="image/png" href="assets/img/favicon.png"><link rel="apple-touch-icon" href="assets/img/logo-icon.png">';
    $o[] = '<link rel="alternate" hreflang="en-ng" href="' . e($canon) . '"><link rel="alternate" hreflang="x-default" href="' . e($canon) . '">';
    $o[] = '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . e(seo_base()) . '/sitemap.xml">';
    $o[] = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    $o[] = '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">';
    $o[] = '<link rel="stylesheet" href="assets/css/style.css?v=9">';
    if (!$noindex) foreach (seo_jsonld($key, $title, $desc, $canon) as $ld)
        $o[] = '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
    if (!$private) $o[] = seo_tracking_head();
    if (seo_cfg('head_code') !== '' && !$private) $o[] = seo_cfg('head_code');   // Super Admin only (trusted)
    echo implode("\n", $o) . "\n";
}

function seo_tracking_head(): string {
    $o = '';
    if (seo_cfg('gtm') !== '') $o .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer'," . json_encode(seo_cfg('gtm')) . ");</script>\n";
    if (seo_cfg('ga4') !== '') $o .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e(seo_cfg('ga4')) . '"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag("js",new Date());gtag("config",' . json_encode(seo_cfg('ga4')) . ");</script>\n";
    if (seo_cfg('fbpixel') !== '') $o .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src='https://connect.facebook.net/en_US/fbevents.js';s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script');fbq('init'," . json_encode(seo_cfg('fbpixel')) . ");fbq('track','PageView');</script>\n";
    if (seo_cfg('clarity') !== '') $o .= "<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,'clarity','script'," . json_encode(seo_cfg('clarity')) . ");</script>\n";
    return rtrim($o);
}
/** Called just before </body> on public pages. */
function seo_body_end(): void {
    if (in_array(seo_script(), SEO_PRIVATE_SCRIPTS, true)) return;
    if (seo_cfg('gtm') !== '') echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e(seo_cfg('gtm')) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
    if (seo_cfg('body_code') !== '') echo seo_cfg('body_code') . "\n";
}

/* ------------------------------------------------------------------ structured data */
function seo_social_links(): array {
    $u = [];
    foreach (['linkedin', 'instagram', 'facebook', 'x', 'youtube', 'tiktok', 'whatsapp'] as $k) if (preg_match('~^https?://~i', seo_cfg('s_' . $k))) $u[] = seo_cfg('s_' . $k);
    foreach (lines(seo_cfg('s_other')) as $l) if (preg_match('~^https?://~i', $l)) $u[] = $l;
    foreach (q("SELECT url FROM seo_presence WHERE status='live' AND url<>''") as $r) if (preg_match('~^https?://~i', $r['url'])) $u[] = $r['url'];
    return array_values(array_unique($u));
}
function seo_org(): array {
    $b = seo_base(); $hasAddr = trim(seo_cfg('biz_street') . seo_cfg('biz_city')) !== '';
    $org = ['@type' => $hasAddr ? [seo_cfg('biz_type') ?: 'ProfessionalService', 'Organization'] : 'Organization', '@id' => $b . '/#organization',
        'name' => seo_cfg('site_name'), 'legalName' => seo_cfg('biz_legal'), 'url' => $b . '/', 'description' => seo_cfg('biz_desc'),
        'logo' => ['@type' => 'ImageObject', 'url' => seo_abs(seo_cfg('biz_logo')), 'width' => 359, 'height' => 356],
        'image' => seo_abs(seo_cfg('og_default')), 'email' => setting('company_email'), 'telephone' => setting('company_phone'),
        'areaServed' => array_map('trim', explode(',', seo_cfg('biz_area') ?: 'Nigeria'))];
    if (seo_cfg('biz_alt') !== '') $org['alternateName'] = seo_cfg('biz_alt');
    if (seo_cfg('biz_founded') !== '') $org['foundingDate'] = seo_cfg('biz_founded');
    if (seo_cfg('biz_price') !== '' && $hasAddr) $org['priceRange'] = seo_cfg('biz_price');
    if ($s = seo_social_links()) $org['sameAs'] = $s;
    if ($hasAddr) {
        $org['address'] = array_filter(['@type' => 'PostalAddress', 'streetAddress' => seo_cfg('biz_street'), 'addressLocality' => seo_cfg('biz_city'), 'addressRegion' => seo_cfg('biz_region'), 'postalCode' => seo_cfg('biz_postal'), 'addressCountry' => seo_cfg('biz_country') ?: 'NG']);
        if (is_numeric(seo_cfg('biz_lat')) && is_numeric(seo_cfg('biz_lng'))) $org['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)seo_cfg('biz_lat'), 'longitude' => (float)seo_cfg('biz_lng')];
        $hrs = [];
        foreach (lines(seo_cfg('biz_hours')) as $l) if (preg_match('~^([A-Za-z,\- ]+?)\s+(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$~', $l, $m)) $hrs[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => seo_days($m[1]), 'opens' => $m[2], 'closes' => $m[3]];
        if ($hrs) $org['openingHoursSpecification'] = $hrs;
    }
    $org['contactPoint'] = [['@type' => 'ContactPoint', 'contactType' => 'customer service', 'telephone' => setting('company_phone'), 'email' => setting('company_email'), 'areaServed' => 'NG', 'availableLanguage' => ['English']]];
    if (function_exists('site_services')) {
        $items = [];
        foreach (site_services() as $s) $items[] = ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $s['title'], 'description' => $s['short'], 'url' => seo_abs('service.php?s=' . $s['slug'])]];
        if ($items) $org['hasOfferCatalog'] = ['@type' => 'OfferCatalog', 'name' => 'Empre Technologies services', 'itemListElement' => $items];
    }
    return $org;
}
function seo_days(string $s): array {
    $map = ['mo' => 'Monday', 'tu' => 'Tuesday', 'we' => 'Wednesday', 'th' => 'Thursday', 'fr' => 'Friday', 'sa' => 'Saturday', 'su' => 'Sunday'];
    $keys = array_keys($map); $out = [];
    foreach (explode(',', $s) as $part) {
        $part = trim($part);
        if (str_contains($part, '-')) {
            [$a, $z] = array_map(fn($x) => strtolower(substr(trim($x), 0, 2)), explode('-', $part, 2));
            $i = array_search($a, $keys, true); $j = array_search($z, $keys, true);
            if ($i !== false && $j !== false) for ($k = $i; $k <= $j; $k++) $out[] = $map[$keys[$k]];
        } elseif (isset($map[strtolower(substr($part, 0, 2))])) $out[] = $map[strtolower(substr($part, 0, 2))];
    }
    return $out ?: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
}
function seo_jsonld(string $key, string $title, string $desc, string $canon): array {
    $b = seo_base(); $g = [seo_org(), ['@type' => 'WebSite', '@id' => $b . '/#website', 'url' => $b . '/', 'name' => seo_cfg('site_name'), 'inLanguage' => 'en-NG', 'publisher' => ['@id' => $b . '/#organization']]];
    $crumb = [['Home', $b . '/']];
    $kind = explode(':', $key)[0];
    if ($kind === 'service' && isset($GLOBALS['svc'])) {
        $s = $GLOBALS['svc'];
        $crumb[] = ['Our Services', seo_abs('services.php')]; $crumb[] = [$s['title'], $canon];
        $g[] = ['@type' => 'Service', '@id' => $canon . '#service', 'name' => $s['title'], 'description' => $desc, 'url' => $canon, 'serviceType' => $s['area_title'] ?? $s['title'], 'provider' => ['@id' => $b . '/#organization'], 'areaServed' => ['@type' => 'Country', 'name' => 'Nigeria']];
    } elseif ($kind === 'course' && isset($GLOBALS['c'])) {
        $c = $GLOBALS['c'];
        $crumb[] = ['Training', seo_abs('training.php')]; $crumb[] = [$c['title'], $canon];
        $course = ['@type' => 'Course', 'name' => $c['title'], 'description' => $desc, 'url' => $canon, 'provider' => ['@id' => $b . '/#organization'],
                   'hasCourseInstance' => array_filter(['@type' => 'CourseInstance', 'courseMode' => $c['mode'] ?: 'Online', 'courseWorkload' => $c['duration'] ?: null])];
        if ((float)$c['fee'] > 0) $course['offers'] = ['@type' => 'Offer', 'price' => (float)$c['fee'], 'priceCurrency' => 'NGN', 'category' => 'Paid', 'url' => $canon];
        $g[] = $course;
    } elseif ($kind === 'job') {
        $crumb[] = ['Careers', seo_abs('careers.php')]; $crumb[] = [explode(' | ', $title)[0], $canon];
    } elseif ($key !== 'index.php' && isset(SEO_STATIC_PAGES[$key])) {
        $crumb[] = [SEO_STATIC_PAGES[$key][0], $canon];
        $g[] = ['@type' => $key === 'about.php' ? 'AboutPage' : ($key === 'contact.php' ? 'ContactPage' : 'WebPage'), '@id' => $canon . '#webpage', 'url' => $canon, 'name' => $title, 'description' => $desc, 'isPartOf' => ['@id' => $b . '/#website'], 'about' => ['@id' => $b . '/#organization']];
    }
    if (count($crumb) > 1) {
        $items = [];
        foreach ($crumb as $i => [$n, $u]) $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $n, 'item' => $u];
        $g[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
    return [['@context' => 'https://schema.org', '@graph' => $g]];
}

/* ------------------------------------------------------------------ sitemap / robots / llms */
function seo_pages_all(): array {
    $out = [];
    foreach (SEO_STATIC_PAGES as $k => [$label, $file]) $out[] = ['key' => $k, 'type' => 'Page', 'label' => $label, 'path' => $file === 'index.php' ? '' : $file, 'updated' => null];
    if (function_exists('site_services')) foreach (site_services() as $s) $out[] = ['key' => 'service:' . $s['slug'], 'type' => 'Service', 'label' => $s['title'], 'path' => 'service.php?s=' . $s['slug'], 'updated' => $s['updated_at'] ?? null, 'desc' => $s['short']];
    foreach (active_courses() as $c) $out[] = ['key' => 'course:' . $c['slug'], 'type' => 'Course', 'label' => $c['title'], 'path' => 'course.php?c=' . $c['slug'], 'updated' => $c['created_at'] ?? null, 'desc' => $c['summary']];
    if (function_exists('job_is_open')) foreach (q("SELECT * FROM jobs WHERE status='Open' ORDER BY sort,id") as $j) if (job_is_open($j)) $out[] = ['key' => 'job:' . $j['slug'], 'type' => 'Job', 'label' => $j['title'], 'path' => 'job.php?j=' . $j['slug'], 'updated' => $j['updated_at'] ?? null, 'desc' => $j['summary']];
    return $out;
}
function seo_sitemap_urls(): array {
    $rows = []; foreach (q("SELECT * FROM seo_pages") as $r) $rows[$r['k']] = $r;
    $urls = [];
    foreach (seo_pages_all() as $p) {
        $r = $rows[$p['key']] ?? null;
        if ($r && $r['robots'] === 'noindex') continue;
        $loc = $r && preg_match('~^https?://~i', (string)$r['canonical']) ? $r['canonical'] : seo_abs($p['path']);
        $lm = $p['updated'] ?: ($r['updated_at'] ?? null);
        if (!$lm && SEO_STATIC_PAGES[$p['key']] ?? null) $lm = date('Y-m-d H:i:s', (int)@filemtime(__DIR__ . '/../' . SEO_STATIC_PAGES[$p['key']][1]) ?: time());
        $urls[] = ['loc' => $loc, 'lastmod' => $lm ? date('Y-m-d', strtotime($lm)) : date('Y-m-d'), 'pri' => $p['path'] === '' ? '1.0' : ($p['type'] === 'Page' ? '0.8' : '0.7')];
    }
    return $urls;
}
function seo_sitemap_xml(): string {
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (seo_sitemap_urls() as $u) $x .= "  <url><loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc><lastmod>{$u['lastmod']}</lastmod><priority>{$u['pri']}</priority></url>\n";
    return $x . "</urlset>\n";
}
function seo_robots_txt(): string {
    $t = "# robots.txt for " . seo_cfg('site_name') . " (managed in Admin > SEO & Visibility)\n";
    if (!seo_indexing_allowed()) return $t . "User-agent: *\nDisallow: /\n";
    $t .= "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /data/\nDisallow: /lib/\nDisallow: /inc/\nDisallow: /send-mail.php\nDisallow: /api-courses.php\n\n";
    if (seo_cfg('ai_crawlers') !== '1') {
        foreach (['GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'ClaudeBot', 'Claude-Web', 'anthropic-ai', 'PerplexityBot', 'Google-Extended', 'CCBot', 'Bytespider', 'Applebot-Extended', 'cohere-ai'] as $bot) $t .= "User-agent: $bot\nDisallow: /\n\n";
    }
    if (trim(seo_cfg('robots_extra')) !== '') $t .= trim(str_replace("\r", '', seo_cfg('robots_extra'))) . "\n\n";
    return $t . "Sitemap: " . seo_base() . "/sitemap.xml\n";
}
function seo_llms_txt(): string {
    $b = seo_base();
    $t = '# ' . seo_cfg('site_name') . "\n\n> " . seo_cfg('biz_desc') . "\n\n";
    $t .= "Website: $b/\nEmail: " . setting('company_email') . "\nPhone: " . setting('company_phone') . "\nServes: " . seo_cfg('biz_area') . "\n\n## Services\n";
    foreach (seo_pages_all() as $p) if ($p['type'] === 'Service') $t .= '- [' . $p['label'] . '](' . seo_abs($p['path']) . '): ' . trim(preg_replace('/\s+/', ' ', (string)($p['desc'] ?? ''))) . "\n";
    $t .= "\n## Training courses\n";
    foreach (seo_pages_all() as $p) if ($p['type'] === 'Course') $t .= '- [' . $p['label'] . '](' . seo_abs($p['path']) . '): ' . trim(preg_replace('/\s+/', ' ', (string)($p['desc'] ?? ''))) . "\n";
    $jobs = array_filter(seo_pages_all(), fn($p) => $p['type'] === 'Job');
    if ($jobs) { $t .= "\n## Open roles\n"; foreach ($jobs as $p) $t .= '- [' . $p['label'] . '](' . seo_abs($p['path']) . '): ' . trim(preg_replace('/\s+/', ' ', (string)($p['desc'] ?? ''))) . "\n"; }
    $t .= "\n## Company\n- [About us](" . seo_abs('about.php') . ")\n- [Contact](" . seo_abs('contact.php') . ")\n- [Careers](" . seo_abs('careers.php') . ")\n- [Sitemap]($b/sitemap.xml)\n";
    if (trim(seo_cfg('llms_extra')) !== '') $t .= "\n" . trim(str_replace("\r", '', seo_cfg('llms_extra'))) . "\n";
    return $t;
}

/* ------------------------------------------------------------------ IndexNow (Bing, Yandex, Seznam, Naver) */
function seo_indexnow_key(): string {
    $k = seo_cfg('indexnow_key');
    if (!preg_match('/^[a-f0-9]{32}$/', $k)) { $k = bin2hex(random_bytes(16)); seo_save('indexnow_key', $k); }
    @file_put_contents(__DIR__ . '/../' . $k . '.txt', $k);   // physical key file (also served by indexnow-key.php if the file cannot be written)
    return $k;
}
function seo_is_local_host(): bool { return (bool)preg_match('~^(localhost|127\.|\[::1\]|.*\.local$|.*\.test$)~i', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')); }
function seo_indexnow(bool $force = false): array {
    if (!function_exists('curl_init')) return [false, 'The PHP cURL extension is not available on this server.'];
    if (!$force && (time() - (int)strtotime(seo_cfg('last_ping') ?: '2000-01-01')) < 60) return [true, 'Skipped (pinged under a minute ago).'];
    $key = seo_indexnow_key(); $host = parse_url(seo_base(), PHP_URL_HOST);
    $list = array_column(seo_sitemap_urls(), 'loc');
    $payload = json_encode(['host' => $host, 'key' => $key, 'keyLocation' => seo_base() . '/' . $key . '.txt', 'urlList' => array_slice($list, 0, 9000)]);
    $ch = curl_init('https://api.indexnow.org/IndexNow');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4]);
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    $ok = in_array($code, [200, 202], true);
    seo_save('last_ping', date('Y-m-d H:i:s') . ' · ' . ($ok ? 'accepted (HTTP ' . $code . ', ' . count($list) . ' URLs)' : 'failed (' . ($code ?: $err ?: 'no response') . ')'));
    return [$ok, $ok ? count($list) . ' URLs sent to Bing, Yandex and other IndexNow search engines.' : 'IndexNow did not accept the request (' . ($code ?: $err ?: 'no response') . '). Make sure the site is live on its public address.'];
}

/* ------------------------------------------------------------------ request hooks: redirects, HTTPS/host, auto-ping */
function seo_boot(): void {
    if (PHP_SAPI === 'cli') return;
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/'); $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $isAdmin = (bool)preg_match('~/admin/~', $script);
    if ($isAdmin) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && seo_cfg('auto_ping') === '1' && !seo_is_local_host()
            && in_array(basename($script), ['job-edit.php', 'jobs.php', 'service-edit.php', 'services.php', 'course-edit.php', 'courses.php', 'seo-page.php'], true))
            register_shutdown_function(function () { ignore_user_abort(true); if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request(); try { seo_indexnow(); } catch (Throwable $e) {} });
        return;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') return;
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? '')); $https = !empty($_SERVER['HTTPS']) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if (!seo_is_local_host() && preg_match('/^[a-z0-9.-]+(:\d+)?$/', $host)) {
        $h = preg_replace('/:\d+$/', '', $host); $newHost = $h; $pref = seo_cfg('host_pref');
        if ($pref === 'www' && !str_starts_with($h, 'www.')) $newHost = 'www.' . $h;
        if ($pref === 'non-www' && str_starts_with($h, 'www.')) $newHost = substr($h, 4);
        $needHttps = seo_cfg('force_https') === '1' && !$https;
        if ($newHost !== $h || $needHttps) { header('Location: ' . ($needHttps || $https ? 'https' : 'http') . '://' . $newHost . $uri, true, 301); exit; }
    }
    $path = rtrim(strtolower((string)parse_url($uri, PHP_URL_PATH)), '/') ?: '/';
    $r = q1("SELECT * FROM seo_redirects WHERE active=1 AND (lower(from_path)=? OR lower(from_path)=?) LIMIT 1", [$path, $path . '/']);
    if ($r && strtolower(rtrim((string)parse_url($r['to_url'], PHP_URL_PATH), '/')) !== $path) {
        run("UPDATE seo_redirects SET hits=hits+1 WHERE id=?", [$r['id']]);
        header('Location: ' . $r['to_url'], true, (int)$r['code'] === 302 ? 302 : 301); exit;
    }
}
function seo_log_404(string $path): void {
    $path = mb_substr(preg_replace('/[\x00-\x1f]/', '', $path), 0, 200);
    if ($path === '' || preg_match('~\.(?:png|jpe?g|gif|svg|ico|css|js|map|woff2?|ttf)$~i', $path) && !str_contains($path, '?')) { /* still log assets, but keep it cheap */ }
    $ref = mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 250); $n = now();
    run("INSERT INTO seo_404(path,hits,referrer,first_seen,last_seen) VALUES(?,1,?,?,?) ON CONFLICT(path) DO UPDATE SET hits=hits+1,last_seen=excluded.last_seen,referrer=CASE WHEN excluded.referrer<>'' THEN excluded.referrer ELSE referrer END", [$path, $ref, $n, $n]);
    if ((int)qv("SELECT COUNT(*) FROM seo_404") > 600) run("DELETE FROM seo_404 WHERE path IN (SELECT path FROM seo_404 ORDER BY resolved DESC, last_seen ASC LIMIT 100)");
}

/* ------------------------------------------------------------------ scoring + content audit */
function seo_len_status(int $n, int $lo, int $hi): string { return $n === 0 ? 'bad' : ($n >= $lo && $n <= $hi ? 'good' : 'warn'); }
/** Checks on what the Super Admin controls (the meta data). Returns [score 0-100, issues[]] */
function seo_score_meta(string $title, string $desc, string $focus, string $robots, string $ogImage = 'x'): array {
    $s = 0; $iss = [];
    $tl = mb_strlen($title); $dl = mb_strlen($desc); $f = mb_strtolower(trim($focus));
    if ($tl === 0) $iss[] = ['bad', 'Missing page title']; else { $s += 15; if ($tl >= 30 && $tl <= 60) $s += 20; elseif ($tl > 60 && $tl <= 70 || $tl >= 20 && $tl < 30) { $s += 10; $iss[] = ['warn', "Title is $tl characters; aim for 30-60"]; } else $iss[] = ['warn', "Title is $tl characters; aim for 30-60"]; }
    if ($dl === 0) $iss[] = ['bad', 'Missing meta description']; else { $s += 10; if ($dl >= 90 && $dl <= 160) $s += 20; elseif ($dl >= 60 && $dl < 90 || $dl > 160 && $dl <= 175) { $s += 10; $iss[] = ['warn', "Description is $dl characters; aim for 90-160"]; } else $iss[] = ['warn', "Description is $dl characters; aim for 90-160"]; }
    if ($f === '') $iss[] = ['warn', 'No focus keyword set']; else { $s += 10; if (str_contains(mb_strtolower($title), $f)) $s += 10; else $iss[] = ['warn', 'Focus keyword is not in the title']; if (str_contains(mb_strtolower($desc), $f)) $s += 10; else $iss[] = ['warn', 'Focus keyword is not in the description']; }
    if ($ogImage !== '') $s += 5;
    if ($robots === 'noindex') { $iss[] = ['info', 'Set to noindex: hidden from search engines']; }
    return [min(100, $s), $iss];
}
function seo_fetch(string $url): array {
    if (!function_exists('curl_init')) return [0, '', 'cURL is not available'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_USERAGENT => 'EmpreSEOAudit/1.0', CURLOPT_SSL_VERIFYPEER => true]);
    $b = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); $t = (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME); curl_close($ch);
    return [$code, (string)$b, $err, $t];
}
/** Look at the real rendered page. Returns ['issues'=>[[level,text]], 'stats'=>[...]] */
function seo_audit_html(string $html, string $focus = ''): array {
    $iss = []; $st = ['words' => 0, 'h1' => 0, 'h2' => 0, 'imgs' => 0, 'noalt' => 0, 'links' => 0];
    $d = new DOMDocument(); libxml_use_internal_errors(true); $d->loadHTML('<?xml encoding="utf-8"?>' . $html); libxml_clear_errors();
    $x = new DOMXPath($d);
    $st['h1'] = $x->query('//h1')->length; $st['h2'] = $x->query('//h2')->length;
    foreach ($x->query('//img') as $im) { $st['imgs']++; if (!$im->hasAttribute('alt')) $st['noalt']++; }
    foreach ($x->query('//a[@href]') as $a) { $h = $a->getAttribute('href'); if ($h !== '' && !preg_match('~^(https?:|mailto:|tel:|#|javascript:)~i', $h)) $st['links']++; }
    $hasCanon = $x->query('//link[@rel="canonical"]')->length > 0; $hasLd = $x->query('//script[@type="application/ld+json"]')->length > 0;
    $hasOg = $x->query('//meta[@property="og:image"]')->length > 0; $noindex = (bool)preg_match('/noindex/i', (string)($x->query('//meta[@name="robots"]/@content')->item(0)->nodeValue ?? ''));
    foreach ($x->query('//script|//style|//noscript|//svg') as $n) $n->parentNode?->removeChild($n);
    $body = $x->query('//main')->item(0) ?? $d->getElementsByTagName('body')->item(0);
    $text = $body ? trim(preg_replace('/\s+/u', ' ', $body->textContent)) : '';
    $st['words'] = $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    if ($st['h1'] === 0) $iss[] = ['bad', 'No H1 heading on the page']; elseif ($st['h1'] > 1) $iss[] = ['warn', $st['h1'] . ' H1 headings (use exactly one)'];
    if ($st['noalt'] > 0) $iss[] = ['warn', $st['noalt'] . ' image(s) without alt text'];
    if ($st['words'] < 250) $iss[] = ['warn', 'Thin content: only ' . $st['words'] . ' words (aim for 300+ on key pages)'];
    if ($st['links'] < 3) $iss[] = ['warn', 'Very few internal links'];
    $f = mb_strtolower(trim($focus));
    if ($f !== '') { $h1 = $x->query('//h1')->item(0); if ($h1 && !str_contains(mb_strtolower($h1->textContent), $f) && !str_contains(mb_strtolower(mb_substr($text, 0, 600)), $f)) $iss[] = ['info', 'Focus keyword not found in the H1 or opening text']; }
    if ($noindex) $iss[] = ['info', 'Page is marked noindex'];
    else { if (!$hasCanon) $iss[] = ['bad', 'No canonical link']; if (!$hasLd) $iss[] = ['warn', 'No structured data (JSON-LD)']; if (!$hasOg) $iss[] = ['warn', 'No social share image']; }
    return ['issues' => $iss, 'stats' => $st];
}

/* ------------------------------------------------------------------ defaults for pages whose text comes from elsewhere + helpers for the console */
function seo_default_meta(string $key): array {
    [$kind, $slug] = array_pad(explode(':', $key, 2), 2, '');
    if ($kind === 'service') foreach (site_services() as $s) if ($s['slug'] === $slug) return [$s['title'] . ' in Nigeria | Empre Technologies', mb_strimwidth($s['title'] . ': ' . trim((string)$s['short']), 0, 158, '…'), mb_strtolower($s['title'])];
    if ($kind === 'course' && ($c = course_by_slug($slug))) return [$c['title'] . ' Course | Empre Technologies Training', mb_strimwidth($c['title'] . ' course: ' . trim((string)$c['summary']), 0, 158, '…'), mb_strtolower($c['title'])];
    if ($kind === 'job' && ($j = job_by_slug($slug))) return [$j['title'] . ' | Careers at ' . $j['company'], mb_strimwidth($j['title'] . ' role at ' . $j['company'] . ': ' . (string)$j['summary'], 0, 158, '…'), mb_strtolower($j['title'])];
    $seed = seo_seed_pages()[$key] ?? null;
    return $seed ? [$seed[0], $seed[1], $seed[2]] : ['', '', ''];
}
/** What a page really uses: the saved override, otherwise the automatic text. */
function seo_effective(string $key): array {
    $row = seo_row($key); [$dt, $dd, $df] = seo_default_meta($key);
    return ['title' => trim((string)($row['title'] ?? '')) !== '' ? $row['title'] : $dt, 'desc' => trim((string)($row['description'] ?? '')) !== '' ? $row['description'] : $dd,
            'focus' => trim((string)($row['focus'] ?? '')) !== '' ? $row['focus'] : $df, 'keywords' => (string)($row['keywords'] ?? ''), 'og' => (string)($row['og_image'] ?? ''), 'canonical' => (string)($row['canonical'] ?? ''),
            'robots' => (string)($row['robots'] ?? 'index'), 'custom' => (bool)$row, 'updated_at' => $row['updated_at'] ?? null];
}
/** Accepts an image upload for share images. Returns [path|null, error|null]. */
function seo_save_image(array $f): array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return [null, 'The image could not be uploaded (it may be larger than the server allows).'];
    if ($f['size'] > 2 * 1024 * 1024) return [null, 'Share images must be 2 MB or smaller.'];
    $i = @getimagesize($f['tmp_name']);
    $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$i['mime'] ?? ''] ?? null;
    if (!$ext) return [null, 'Please upload a PNG, JPG or WebP image.'];
    if ($i[0] < 600 || $i[1] < 315) return [null, 'The image is too small. Use at least 1200 x 630 pixels.'];
    $dir = __DIR__ . '/../assets/img/seo'; if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return [null, 'Could not save the image. Check that assets/img/seo is writable.'];
    return ['assets/img/seo/' . $name, null];
}
/** Accept a bare code OR a pasted <meta ... content="CODE"> tag. */
function seo_clean_token(string $v): string {
    $v = trim($v);
    if (preg_match('~content=["\']([^"\']+)["\']~i', $v, $m)) $v = $m[1];
    return preg_match('/^[A-Za-z0-9_\-]{8,120}$/', $v) ? $v : '';
}
const SEO_PRESENCE = [
    ['Search engines', [
        ['gsc', 'Google Search Console', 'Shows how Google sees your site. Verify the site, then submit sitemap.xml once.', 'https://search.google.com/search-console'],
        ['gbp', 'Google Business Profile', 'Puts Empre on Google Search and Maps and lets customers leave reviews. The single biggest local-search win.', 'https://business.google.com'],
        ['bingwm', 'Bing Webmaster Tools', 'Covers Bing, Yahoo, DuckDuckGo and the AI assistants built on Bing. You can import straight from Search Console.', 'https://www.bing.com/webmasters'],
        ['bingplaces', 'Bing Places', 'Free business listing on Bing Maps.', 'https://www.bingplaces.com'],
        ['apple', 'Apple Business Connect', 'Your listing in Apple Maps and Siri.', 'https://businessconnect.apple.com']]],
    ['Social profiles', [
        ['linkedin', 'LinkedIn company page', 'Essential for B2B. Make sure the page links to the website.', 'https://www.linkedin.com/company/setup/new/'],
        ['facebook', 'Facebook page', 'Also gives you Facebook/Instagram ads and share previews.', 'https://www.facebook.com/pages/create'],
        ['instagram', 'Instagram', 'Add the website link in the bio.', 'https://www.instagram.com'],
        ['x', 'X (Twitter)', 'Claim the handle, then add it in the Technical tab.', 'https://x.com'],
        ['youtube', 'YouTube channel', 'Video tutorials and training teasers rank well and feed Google.', 'https://www.youtube.com'],
        ['tiktok', 'TikTok', 'Short tips and training clips.', 'https://www.tiktok.com'],
        ['whatsapp', 'WhatsApp Business', 'Catalogue, quick replies and a click-to-chat link.', 'https://business.whatsapp.com']]],
    ['Directories & reviews', [
        ['clutch', 'Clutch', 'Verified B2B reviews that buyers search for.', 'https://clutch.co'],
        ['goodfirms', 'GoodFirms', 'Software and IT services directory.', 'https://www.goodfirms.co'],
        ['crunchbase', 'Crunchbase', 'Company profile that search engines and AI assistants trust.', 'https://www.crunchbase.com'],
        ['ngdir', 'Nigerian business directories', 'List Empre (same name, address and phone everywhere) in the main Nigerian directories.', ''],
        ['reviews', 'First 5 Google reviews', 'Ask happy clients and past students to review the Business Profile.', ''],
        ['backlinks', 'Links from partners', 'Ask partners, vendors and training alumni to link to empretechnologies.com.ng.', '']]],
];
