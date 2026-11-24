<?php
declare(strict_types=1);
/* =====================================================================
 * Careers & applicant portal
 *   jobs                 vacancies shown on careers.php (managed in Admin > Careers)
 *   applicants           people who applied (sign in to the applicant portal)
 *   applications         one row per job a person applied for
 *   application_files    extra documents uploaded after applying
 *   application_events   the timeline (what the applicant and the admin see)
 *   throttle             tiny rate-limit log (login / apply attempts)
 * Uploaded CVs live in data/uploads/ (blocked from the web by .htaccess and only ever
 * served through PHP after a permission check).
 * ===================================================================== */

const JOB_STATUSES = ['Draft', 'Open', 'Closed'];
const APP_STATUSES = ['Received', 'Under Review', 'Shortlisted', 'Interview', 'More Details Needed', 'Accepted', 'Hired', 'Rejected', 'Not Qualified'];
const JOB_TYPES    = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Full-time / Part-time / Contract'];
const HIRING_STEPS = [
    ['Application review', 'We read every application carefully.'],
    ['20-minute intro call', 'A friendly first conversation.'],
    ['Short practical task', 'A mock demo, a content sample or a support scenario.'],
    ['Final conversation', 'Meet the founder and ask us anything.'],
    ['Offer', 'We confirm the details and welcome you aboard.'],
];
const UPLOAD_TYPES = [
    'pdf'  => ['application/pdf', "%PDF-"],
    'doc'  => ['application/msword', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', "PK\x03\x04"],
    'png'  => ['image/png', "\x89PNG"],
    'jpg'  => ['image/jpeg', "\xFF\xD8\xFF"],
    'jpeg' => ['image/jpeg', "\xFF\xD8\xFF"],
];
const CV_TYPES = ['pdf', 'doc', 'docx'];
const DOC_TYPES = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];

/* ------------------------------------------------------------------ schema */
function careers_schema(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS jobs(
        id INTEGER PRIMARY KEY, slug TEXT NOT NULL UNIQUE, title TEXT NOT NULL, company TEXT DEFAULT 'Empre Technologies', department TEXT, type TEXT,
        location TEXT, work_mode TEXT, reports_to TEXT, pay TEXT, icon TEXT DEFAULT 'users', summary TEXT, about_company TEXT, role_desc TEXT,
        responsibilities TEXT, requirements TEXT, nice_to_have TEXT, success TEXT, benefits TEXT, extra_question TEXT, extra_required INTEGER DEFAULT 0,
        closing_date TEXT, start_date TEXT, status TEXT DEFAULT 'Draft', sort INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT);
    CREATE TABLE IF NOT EXISTS applicants(
        id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE COLLATE NOCASE, phone TEXT, password_hash TEXT NOT NULL, created_at TEXT, last_login TEXT);
    CREATE TABLE IF NOT EXISTS applications(
        id INTEGER PRIMARY KEY, code TEXT NOT NULL UNIQUE, job_id INTEGER NOT NULL REFERENCES jobs(id), applicant_id INTEGER NOT NULL REFERENCES applicants(id),
        name TEXT, email TEXT, phone TEXT, linkedin TEXT, cv_file TEXT, cv_name TEXT, cover_letter TEXT, answer TEXT,
        status TEXT DEFAULT 'Received', edit_unlocked INTEGER DEFAULT 0, info_request TEXT, decision_note TEXT, admin_note TEXT, reviewed_by TEXT,
        created_at TEXT, updated_at TEXT, UNIQUE(job_id, applicant_id));
    CREATE TABLE IF NOT EXISTS application_files(
        id INTEGER PRIMARY KEY, application_id INTEGER NOT NULL REFERENCES applications(id), file TEXT NOT NULL, original TEXT, label TEXT, created_at TEXT);
    CREATE TABLE IF NOT EXISTS application_events(
        id INTEGER PRIMARY KEY, application_id INTEGER NOT NULL REFERENCES applications(id), kind TEXT, text TEXT, actor TEXT, visible INTEGER DEFAULT 1, created_at TEXT);
    CREATE TABLE IF NOT EXISTS throttle(k TEXT NOT NULL, ts INTEGER NOT NULL);
    CREATE INDEX IF NOT EXISTS ix_app_job ON applications(job_id, status);
    CREATE INDEX IF NOT EXISTS ix_app_applicant ON applications(applicant_id);
    CREATE INDEX IF NOT EXISTS ix_app_ev ON application_events(application_id);
    CREATE INDEX IF NOT EXISTS ix_throttle ON throttle(k, ts);
    ");
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='jobs_seeded'")->fetch()) {
        if (!(int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn()) seed_jobs($pdo);
        $pdo->exec("INSERT OR IGNORE INTO settings(k,v) VALUES('jobs_seeded','1')");
    }
}

/* ------------------------------------------------------------------ jobs */
function job_is_open(array $j): bool {
    if (($j['status'] ?? '') !== 'Open') return false;
    return empty($j['closing_date']) || $j['closing_date'] >= date('Y-m-d');
}
/** What the public sees: Open, Closed (manually or past the closing date), or Draft. */
function job_state(array $j): string {
    if ($j['status'] === 'Draft') return 'Draft';
    return job_is_open($j) ? 'Open' : 'Closed';
}
function open_jobs(): array {
    return array_values(array_filter(q("SELECT * FROM jobs WHERE status='Open' ORDER BY sort, id"), 'job_is_open'));
}
function job_by_slug(string $slug): ?array {
    $j = q1("SELECT * FROM jobs WHERE slug=? AND status<>'Draft'", [$slug]);
    return $j ?: null;
}
function job_milestones(?string $t): array {
    $o = [];
    foreach (lines($t) as $l) {
        $p = explode(':', $l, 2);
        $o[] = count($p) === 2 ? [trim($p[0]), trim($p[1])] : ['', $l];
    }
    return $o;
}
function job_employment_type(string $t): string {
    $t = strtolower($t);
    if (str_contains($t, 'contract')) return 'CONTRACTOR';
    if (str_contains($t, 'part')) return 'PART_TIME';
    if (str_contains($t, 'intern')) return 'INTERN';
    return 'FULL_TIME';
}

/* ------------------------------------------------------------------ site url / mail */
function site_url(string $path = ''): string {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^[A-Za-z0-9.-]+(:\d{1,5})?$/', $host)) $host = trim(setting('company_web')) ?: 'localhost';
    $https = !empty($_SERVER['HTTPS']) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $dir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    $dir = preg_replace('#/admin$#', '', $dir);
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/' . ltrim($path, '/');
}
function careers_inbox(): string { $c = trim(setting('careers_email')); return filter_var($c, FILTER_VALIDATE_EMAIL) ? $c : NOTIFY_EMAIL; }

/* ------------------------------------------------------------------ rate limiting */
function client_ip(): string { return (string)($_SERVER['REMOTE_ADDR'] ?? '0'); }
function throttled(string $key, int $max, int $windowSec): bool {
    db()->prepare("DELETE FROM throttle WHERE ts < ?")->execute([time() - 86400]);
    return (int)qv("SELECT COUNT(*) FROM throttle WHERE k=? AND ts>?", [$key, time() - $windowSec]) >= $max;
}
function throttle_hit(string $key): void { run("INSERT INTO throttle(k,ts) VALUES(?,?)", [$key, time()]); }

/* ------------------------------------------------------------------ applicant accounts */
function applicant_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    $id = (int)($_SESSION['applicant']['id'] ?? 0);
    if ($id) { $cache = q1("SELECT id,name,email,phone FROM applicants WHERE id=?", [$id]); if (!$cache) unset($_SESSION['applicant']); }
    return $cache;
}
function applicant_login(int $id): void {
    session_regenerate_id(true);
    $_SESSION['applicant'] = ['id' => $id];
    run("UPDATE applicants SET last_login=? WHERE id=?", [now(), $id]);
}
function require_applicant(): array {
    $u = applicant_user();
    if (!$u) {
        $next = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'portal.php')) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        flash('Please sign in to your applicant portal.', 'err');
        redirect('portal-login.php?next=' . urlencode($next));
    }
    return $u;
}
/** Only allow redirects to our own applicant/careers pages. */
function safe_next(string $n, string $fallback = 'portal.php'): string {
    return preg_match('/^(portal|portal-app|apply|job|careers)\.php(\?[A-Za-z0-9=&_%.-]*)?$/', $n) ? $n : $fallback;
}
function app_event(int $appId, string $kind, string $text, string $actor, bool $visible = true): void {
    run("INSERT INTO application_events(application_id,kind,text,actor,visible,created_at) VALUES(?,?,?,?,?,?)", [$appId, $kind, $text, $actor, $visible ? 1 : 0, now()]);
}

/* ------------------------------------------------------------------ application statuses */
/** Plain-language explanation shown to the applicant. [headline, message, tone] */
function app_status_info(string $s): array {
    return match ($s) {
        'Received'            => ['Application received', 'Thank you for applying. Our team will review your application shortly.', 'info'],
        'Under Review'        => ['Under review', 'Your application is being reviewed by the hiring team. We will update this page as soon as there is news.', 'info'],
        'More Details Needed' => ['Action needed: more details required', 'The hiring team needs a little more information from you. Please update your application below.', 'warn'],
        'Shortlisted'         => ['You are shortlisted', 'Good news. Your profile stood out and you are on our shortlist. We will contact you about the next step.', 'good'],
        'Interview'           => ['Interview stage', 'We would like to speak with you. See the interview details below and watch your email.', 'good'],
        'Hired'               => ['Welcome to the team', 'Congratulations and welcome aboard! Complete your onboarding checklist below to get ready for day one.', 'good'],
        'Accepted'            => ['Congratulations, application accepted', 'We would love to take this further. The team will contact you with the next steps.', 'good'],
        'Rejected'            => ['Not moving forward', 'Thank you for your interest. We will not be taking your application forward at this time.', 'bad'],
        'Not Qualified'       => ['Requirements not met', 'Thank you for applying. After review, your profile does not currently meet the requirements for this role.', 'bad'],
        default               => [$s, '', 'info'],
    };
}
/** 0..2 position on the 3-step tracker: Submitted, In review, Decision */
function app_stage(string $s): int {
    return match ($s) { 'Received' => 0, 'Under Review', 'Shortlisted', 'Interview', 'More Details Needed' => 1, default => 2 };
}

/* ------------------------------------------------------------------ uploads */
function upload_dir(): string {
    $d = dirname(DB_PATH) . '/uploads';
    if (!is_dir($d)) { @mkdir($d, 0750, true); }
    if (is_dir($d) && !is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    return $d;
}
function ini_bytes(string $k): int {
    $v = trim((string)ini_get($k)); if ($v === '') return 0;
    $n = (float)$v; $u = strtolower(substr($v, -1));
    return (int)($u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n)));
}
/** Largest file we accept: 5 MB, or the server's own limit if that is lower. */
function upload_limit(): int {
    $l = 5 * 1048576;
    foreach (['upload_max_filesize', 'post_max_size'] as $k) { $b = ini_bytes($k); if ($b > 0) $l = min($l, $b); }
    return $l;
}
function upload_limit_label(): string { return number_format(upload_limit() / 1048576, upload_limit() % 1048576 ? 1 : 0) . ' MB'; }
/** Validate and store one uploaded file. Returns [storedName|null, originalName|null, error|null]. */
function store_upload(?array $f, array $allowed): array {
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null, 'nofile'];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) return [null, null, 'The file is too large (maximum ' . upload_limit_label() . ').'];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return [null, null, 'The file could not be uploaded. Please try again.'];
    if ($f['size'] > upload_limit()) return [null, null, 'The file is too large (maximum ' . upload_limit_label() . ').'];
    $orig = preg_replace('/[\x00-\x1F\x7F\/\\\\]+/', '', basename((string)$f['name']));
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true) || !isset(UPLOAD_TYPES[$ext])) return [null, null, 'Unsupported file type. Allowed: ' . strtoupper(implode(', ', array_unique($allowed))) . '.'];
    $h = (string)file_get_contents($f['tmp_name'], false, null, 0, 8);
    if (strncmp($h, UPLOAD_TYPES[$ext][1], strlen(UPLOAD_TYPES[$ext][1])) !== 0) return [null, null, 'That file does not look like a valid ' . strtoupper($ext) . ' document.'];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], upload_dir() . '/' . $name)) return [null, null, 'The file could not be saved on the server.'];
    @chmod(upload_dir() . '/' . $name, 0640);
    return [$name, mb_substr($orig, 0, 120), null];
}
function delete_upload(?string $stored): void {
    if ($stored && preg_match('/^[a-f0-9]{32}\.[a-z]{3,4}$/', $stored)) @unlink(upload_dir() . '/' . $stored);
}
/** Stream a stored file to the browser as a download (callers must check permission first). */
function send_stored_file(string $stored, string $downloadName): never {
    $p = upload_dir() . '/' . $stored;
    if (!preg_match('/^[a-f0-9]{32}\.[a-z]{3,4}$/', $stored) || !is_file($p)) { http_response_code(404); exit('File not found.'); }
    $ext = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
    header('Content-Type: ' . (UPLOAD_TYPES[$ext][0] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($p));
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._ -]+/', '_', $downloadName) . '"');
    header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
    readfile($p); exit;
}

/* ------------------------------------------------------------------ CSV */
function csv_cell($v): string {
    $s = (string)$v;
    return ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) ? "'" . $s : $s;   // stop spreadsheet formula injection
}
function csv_download(string $filename, array $header, array $rows): never {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, no-store');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, $header);
    foreach ($rows as $r) fputcsv($o, array_map('csv_cell', $r));
    fclose($o); exit;
}

/* ------------------------------------------------------------------ notifications */
function mail_new_application(array $app, array $job): void {
    send_mail(careers_inbox(), 'New job application ' . $app['code'] . ' - ' . $job['title'],
        "A new application was received.\n\nReference: {$app['code']}\nRole: {$job['title']} ({$job['company']})\nName: {$app['name']}\nEmail: {$app['email']}\nPhone: {$app['phone']}\nLinkedIn: {$app['linkedin']}\n\nReview it in the admin: " . site_url('admin/application.php?id=' . $app['id']) . "\n", $app['email']);
    hr_notify('received', $app, $job);   // styled confirmation to the applicant (editable under Careers > Email templates)
}

/* ------------------------------------------------------------------ first set of jobs (from the Conversa AI vacancies document) */
function seed_jobs(PDO $pdo): void {
    $about = "Most Nigerian businesses sell through WhatsApp, Instagram and DMs, and most of them lose customers to slow replies, missed messages and messy follow-up. Conversa AI gives them one shared inbox, smart automated replies, team roles and billing in a single place, so no customer is left waiting.\n\nWe are an early-stage team. You will have real ownership, direct access to the founder, and a visible impact on the company from your first week.";
    $benefits = "A chance to join an early-stage product and grow with it\nDirect work with the founder and real influence on the product\nPerformance rewards: commission, bonuses or growth opportunities\nFree use of Conversa AI, training and a learning budget\nA friendly, fast-moving, remote-friendly team culture";
    $jobs = [
     ['sales-and-onboarding-lead', 'Sales and Onboarding Lead', 'Sales', 'Full-time', 'Founder', 'Base salary + commission on every business you bring on and keep', 'handshake',
      'Find businesses that need us, show them what Conversa AI can do and personally get every new customer set up.',
      "You are our most important hire. You will find businesses that need us, show them what Conversa AI can do, and personally get them set up so they see value in their first week. In Nigeria, small businesses buy from people they trust. You will be that person.",
      "Find and qualify prospects: SMEs, online vendors, salons, clinics, schools, agencies and other WhatsApp-heavy businesses\nReach out by WhatsApp, calls, email and social media, and book demos\nRun clear, friendly demos that show how Conversa AI solves the customer's specific problem\nClose sales: agree the plan, handle objections and get the first payment\nOnboard every new customer: connect their WhatsApp number, set up the inbox, invite their team and set up automated replies\nMake sure each customer gets a real result within seven days, then check in at day 14 and day 30\nKeep our CRM up to date and report weekly on leads, demos, sales and onboarding progress\nShare what you hear from customers with the founder to shape the product",
      "2+ years of B2B or SME sales experience (SaaS, fintech, telecoms, digital services or similar is a plus)\nConfident and warm on calls, WhatsApp and in person\nComfortable with tech: you can learn a new tool quickly and explain it in simple language\nStrong at following up. You do not let a lead go cold\nGood understanding of how Nigerian small businesses operate and make buying decisions\nClear written and spoken English. Yoruba, Pidgin, Hausa or Igbo is a plus\nSelf-driven, honest and target-focused",
      "Experience with WhatsApp Business, a CRM, or selling subscriptions\nAn existing network of SME owners",
      "First 30 days: Learn the product deeply, run your first demos, onboard your first customers.\nFirst 60 days: A steady weekly pipeline and consistent closed sales.\nFirst 90 days: Hit your monthly sales target, with new customers active and using the product.",
      "Share one example of a deal or target you hit."],
     ['content-and-social-media-marketer', 'Content and Social Media Marketer', 'Marketing', 'Full-time / Part-time / Contract', 'Founder', '', 'spark',
      'Make Conversa AI visible and credible with simple stories, short videos and content that turns attention into sign-ups.',
      "You will make Conversa AI visible and credible. Your job is to show, in simple stories and short videos, how a salon, shop or school stopped losing customers on WhatsApp, and to turn that attention into sign-ups.",
      "Plan and run our content calendar on Instagram, LinkedIn, X and TikTok (and WhatsApp Status and Facebook where useful)\nWrite short case studies, tips and before-and-after stories from real customers\nCreate posts, carousels, short videos and reels. You can shoot and edit simple videos on your phone\nWrite captions, newsletters, landing page copy and ad copy that is clear and persuasive\nCollect customer testimonials, screenshots and results with the Customer Success team\nGrow our community: reply to comments and DMs, join conversations and build relationships with business owners and creators\nWork with the sales lead on lead-generating campaigns and with influencers, communities and partners\nTrack what works (reach, engagement, clicks, sign-ups) and report monthly",
      "1-3 years of social media or content experience, ideally for a tech, SaaS or small-business brand\nExcellent writing: simple, clear and engaging\nA good eye for design. You are comfortable with Canva, CapCut or similar tools\nUnderstanding of what works on each platform, including short-form video\nCurious about AI, small business and WhatsApp commerce\nOrganised, creative and able to deliver consistently",
      "Basic paid ads (Meta Ads), SEO or email marketing\nPhotography or video editing\nA portfolio of accounts you have grown",
      "First 30 days: Content calendar live, consistent posting, first case study published.\nFirst 60 days: Measurable growth in followers, engagement and website visits.\nFirst 90 days: Content that reliably brings in leads and demo requests.",
      "Share links to content you created or accounts you grew."],
     ['customer-success-and-support-agent', 'Customer Success and Support Agent', 'Customer Success', 'Full-time', 'Founder / Sales and Onboarding Lead', 'Base salary', 'headset',
      'Keep customers happy and subscribed: answer questions, fix setup problems and turn happy customers into referrals.',
      "You keep our customers happy and keep them subscribed. You answer questions, fix setup problems, help customers get more from the product, and turn happy customers into testimonials and referrals. You will do this using our own inbox, so you will know the product better than anyone.",
      "Respond to customer questions and issues by chat, email, WhatsApp and phone quickly and kindly\nHelp customers with setup: WhatsApp connection, team invites, roles, automated replies, billing and payments\nSolve problems in the first response where you can, and escalate clearly to the founder or developer when you cannot\nWatch for customers who are inactive or struggling, and reach out before they cancel\nAsk happy customers for testimonials, reviews and referrals, and pass the best ones to marketing\nKeep our help articles, FAQs and canned replies up to date\nRecord bugs, feature requests and common questions, and report them to the team each week\nTrack response times, customer satisfaction and renewals",
      "1+ year of customer support, customer success or account management experience (SaaS or online business preferred)\nPatient, friendly and clear communicator in writing and on calls\nComfortable with technology and quick to learn new tools. You can explain technical steps to non-technical people\nOrganised, reliable and good at following things through\nAble to work scheduled hours, including some weekends or evenings on a rota",
      "Experience with WhatsApp Business, helpdesk tools, or onboarding software customers",
      "First 30 days: Know the product, handle tickets independently, response times within target.\nFirst 60 days: High satisfaction scores, customer questions turned into new help articles.\nFirst 90 days: Lower churn, regular testimonials and referrals coming in.",
      "How would you handle an upset customer whose WhatsApp will not connect?"],
    ];
    $st = $pdo->prepare("INSERT INTO jobs(slug,title,company,department,type,location,work_mode,reports_to,pay,icon,summary,about_company,role_desc,responsibilities,requirements,nice_to_have,success,benefits,extra_question,extra_required,closing_date,start_date,status,sort,created_at,updated_at)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NULL,'','Open',?,?,?)");
    foreach ($jobs as $i => $j) {
        $st->execute([$j[0], $j[1], 'Conversa AI', $j[2], $j[3], 'Nigeria', '', $j[4], $j[5], $j[6], $j[7], $about, $j[8], $j[9], $j[10], $j[11], $j[12], $benefits, $j[13], $i + 1, now(), now()]);
    }
}

/** Inline icon from the site sprite (head.php). */
function svg_icon(string $n): string { return '<svg class="ic" aria-hidden="true" viewBox="0 0 24 24"><use href="#i-' . e($n) . '"/></svg>'; }
/** "2 days ago" / date */
function ago(string $ts): string {
    $d = time() - strtotime($ts);
    if ($d < 3600) return max(1, (int)($d / 60)) . ' min ago';
    if ($d < 86400) return (int)($d / 3600) . ' h ago';
    if ($d < 86400 * 8) return (int)($d / 86400) . ' day' . ((int)($d / 86400) === 1 ? '' : 's') . ' ago';
    return dt($ts);
}
