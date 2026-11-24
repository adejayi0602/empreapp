<?php
declare(strict_types=1);
/* =====================================================================
 * Mini HR layer on top of Careers
 *   1. Application form controls   cf_<field> settings: required / optional / hidden
 *   2. Automated applicant emails  mail_templates (editable) + mail_log (every email, resendable)
 *   3. Hiring pipeline             hr_set_status() is the ONE place a status changes
 *   4. Onboarding                  onboarding_template -> onboardings -> onboarding_tasks
 * ===================================================================== */

const APP_STATUSES_ALL = ['Received', 'Under Review', 'Shortlisted', 'Interview', 'More Details Needed', 'Accepted', 'Hired', 'Rejected', 'Not Qualified'];
const PIPELINE_COLUMNS = ['Received', 'Under Review', 'More Details Needed', 'Shortlisted', 'Interview', 'Accepted', 'Hired', 'Rejected', 'Not Qualified'];
const ONB_CATEGORIES   = ['Documents', 'Accounts & access', 'Orientation', 'Training', 'Check-ins'];
const ONB_OWNERS       = ['Employee', 'HR', 'Manager', 'IT'];

/* name / email / portal password / consent are always required: they create the applicant's account. */
const APP_FIELDS = [
    'phone'          => ['Phone number',            'required', 'How the team reaches the applicant quickly.'],
    'linkedin'       => ['LinkedIn profile',        'required', 'Must be a linkedin.com link when given.'],
    'cv'             => ['CV upload',               'required', 'PDF, DOC or DOCX.'],
    'cover_letter'   => ['Cover letter',            'required', 'Why they are the best fit. Minimum length below.'],
    'portfolio'      => ['Portfolio or website',    'hidden',   'A link to their work (Behance, GitHub, personal site).'],
    'location'       => ['Current city / location', 'hidden',   'Useful for on-site or hybrid roles.'],
    'expected_pay'   => ['Expected pay',            'hidden',   'Free text, e.g. ₦250,000 per month.'],
    'available_from' => ['Earliest start date',     'hidden',   'A date picker.'],
];
const FIELD_MODES = ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'];

/* ------------------------------------------------------------------ schema */
function hr_schema(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS mail_templates(
        k TEXT PRIMARY KEY, name TEXT, subject TEXT, headline TEXT, body TEXT, button TEXT, link TEXT DEFAULT 'portal', tone TEXT DEFAULT 'info',
        enabled INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, updated_at TEXT);
    CREATE TABLE IF NOT EXISTS mail_log(
        id INTEGER PRIMARY KEY, to_email TEXT, subject TEXT, template TEXT, application_id INTEGER, status TEXT, body_html TEXT, body_text TEXT, sent_by TEXT, created_at TEXT);
    CREATE TABLE IF NOT EXISTS onboarding_template(
        id INTEGER PRIMARY KEY, title TEXT NOT NULL, description TEXT, category TEXT, owner TEXT DEFAULT 'HR', due_days INTEGER DEFAULT 0, needs_file INTEGER DEFAULT 0, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE IF NOT EXISTS onboardings(
        id INTEGER PRIMARY KEY, application_id INTEGER NOT NULL UNIQUE REFERENCES applications(id), status TEXT DEFAULT 'In progress',
        start_date TEXT, manager TEXT, notes TEXT, created_at TEXT, completed_at TEXT);
    CREATE TABLE IF NOT EXISTS onboarding_tasks(
        id INTEGER PRIMARY KEY, onboarding_id INTEGER NOT NULL REFERENCES onboardings(id), title TEXT NOT NULL, description TEXT, category TEXT, owner TEXT DEFAULT 'HR',
        due_date TEXT, needs_file INTEGER DEFAULT 0, done INTEGER DEFAULT 0, done_at TEXT, done_by TEXT, file_id INTEGER, sort INTEGER DEFAULT 0);
    CREATE INDEX IF NOT EXISTS ix_mail_log_app ON mail_log(application_id);
    CREATE INDEX IF NOT EXISTS ix_onb_task ON onboarding_tasks(onboarding_id);
    ");
    $cols = array_column($pdo->query("PRAGMA table_info(applications)")->fetchAll(), 'name');
    foreach (['portfolio' => 'TEXT', 'location' => 'TEXT', 'expected_pay' => 'TEXT', 'available_from' => 'TEXT', 'interview_at' => 'TEXT', 'interview_info' => 'TEXT', 'hired_at' => 'TEXT'] as $c => $t)
        if (!in_array($c, $cols, true)) $pdo->exec("ALTER TABLE applications ADD COLUMN $c $t");
    $st = $pdo->prepare("INSERT OR IGNORE INTO mail_templates(k,name,subject,headline,body,button,link,tone,enabled,sort,updated_at) VALUES(?,?,?,?,?,?,?,?,1,?,?)");
    foreach (hr_default_templates() as $i => $t) $st->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $i, date('Y-m-d H:i:s')]);
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='onb_seeded'")->fetch()) {
        if (!(int)$pdo->query("SELECT COUNT(*) FROM onboarding_template")->fetchColumn()) {
            $ins = $pdo->prepare("INSERT INTO onboarding_template(title,description,category,owner,due_days,needs_file,sort) VALUES(?,?,?,?,?,?,?)");
            foreach (hr_default_onboarding() as $i => $r) $ins->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $i + 1]);
        }
        $pdo->exec("INSERT OR IGNORE INTO settings(k,v) VALUES('onb_seeded','1')");
    }
}
function hr_default_onboarding(): array {
    // [title, description, category, owner, due days after start date, needs file]
    return [
        ['Sign your offer letter / employment agreement', 'Download it from your email, sign it and upload the signed copy.', 'Documents', 'Employee', -3, 1],
        ['Upload a valid ID', 'NIN slip, international passport, driver\'s licence or voter\'s card.', 'Documents', 'Employee', -3, 1],
        ['Upload a passport photograph', 'A clear, recent photo on a plain background.', 'Documents', 'Employee', -3, 1],
        ['Send your bank details for payroll', 'Account name, account number and bank. Share them privately with HR.', 'Documents', 'Employee', -2, 0],
        ['Share your Tax ID (TIN) and pension details', 'Needed for payroll and statutory deductions.', 'Documents', 'Employee', -2, 0],
        ['Create the company email account', '', 'Accounts & access', 'IT', -1, 0],
        ['Add to the team chat and shared drive', '', 'Accounts & access', 'IT', -1, 0],
        ['Set up tool accounts (CRM, Conversa AI, project board)', '', 'Accounts & access', 'IT', 0, 0],
        ['Prepare laptop and equipment', '', 'Accounts & access', 'Manager', -1, 0],
        ['Welcome call with the founder', 'A short chat about the company, the product and your first weeks.', 'Orientation', 'Manager', 0, 0],
        ['Meet the team', '', 'Orientation', 'Manager', 1, 0],
        ['Read the company handbook and policies', '', 'Orientation', 'Employee', 3, 0],
        ['Product training: Conversa AI walkthrough', '', 'Training', 'Manager', 5, 0],
        ['Agree your 30-60-90 day goals', '', 'Training', 'Manager', 7, 0],
        ['First-week check-in', 'How are things going? Anything missing?', 'Check-ins', 'HR', 7, 0],
        ['30-day review', '', 'Check-ins', 'Manager', 30, 0],
    ];
}
function hr_default_templates(): array {
    // [key, name, subject, headline, body, button, link(portal|careers), tone]
    $sign = "\n\nEmpre Technologies Careers";
    return [
        ['received', 'Application received', 'We received your application: {job} ({code})', 'Application received',
            "Hello {first_name},\n\nThank you for applying for {job} at {company}. We have received your application and our team will review it shortly.\n\nYour reference number is {code}. You can follow your application, see its status and upload anything we ask for in your applicant portal.\n\nWe read every application carefully, so please allow a few days for an update.", 'Open my applicant portal', 'portal', 'info'],
        ['under_review', 'Under review', 'Your application is under review: {job}', 'Your application is under review',
            "Hello {first_name},\n\nYour application for {job} is now being reviewed by our hiring team.\n\n{message}\n\nWe will email you as soon as there is news. You can also check progress at any time in your portal.", 'Track my application', 'portal', 'info'],
        ['shortlisted', 'Shortlisted', 'Good news: you are shortlisted for {job}', 'You are shortlisted',
            "Hello {first_name},\n\nGood news! After reviewing the applications for {job}, we would like to take you forward. You are on our shortlist.\n\n{message}\n\nWe will contact you shortly about the next step.", 'Track my application', 'portal', 'good'],
        ['interview', 'Interview invitation', 'Interview invitation: {job}', 'Let\'s talk',
            "Hello {first_name},\n\nWe would like to invite you to an interview for {job} at {company}.\n\nWhen: {interview_when}\n\nDetails: {interview_info}\n\n{message}\n\nIf the time does not work for you, simply reply to this email and we will find another slot.", 'View interview details', 'portal', 'good'],
        ['more_details', 'More details needed', 'Action needed: more details for your {job} application', 'We need a little more from you',
            "Hello {first_name},\n\nThank you for applying for {job}. Before we can continue, we need a bit more information from you:\n\n{message}\n\nPlease sign in to your applicant portal, update your application and send it back to us. Your application stays open for editing until you send your update.", 'Update my application', 'portal', 'warn'],
        ['accepted', 'Application accepted', 'Congratulations! Your application was accepted: {job}', 'Congratulations!',
            "Hello {first_name},\n\nWe are delighted to tell you that your application for {job} at {company} has been accepted.\n\n{message}\n\nOur team will contact you with the next steps. You can follow everything in your applicant portal.", 'Open my applicant portal', 'portal', 'good'],
        ['hired', 'Welcome aboard (onboarding starts)', 'Welcome to {company}!', 'Welcome to the team',
            "Hello {first_name},\n\nWelcome aboard! We are thrilled to have you join {company} as {job}.\n\nYour start date: {start_date}\n\n{message}\n\nTo get ready for day one we have prepared an onboarding checklist for you. Sign in to your portal to complete your tasks, upload your documents and see what our team is preparing for you.", 'Start my onboarding', 'portal', 'good'],
        ['rejected', 'Application not successful', 'Update on your application: {job}', 'Thank you for applying',
            "Hello {first_name},\n\nThank you for taking the time to apply for {job} at {company} and for your interest in joining us.\n\nAfter careful consideration, we will not be taking your application forward at this time.\n\n{message}\n\nThis was not an easy decision. We encourage you to keep an eye on our careers page for future roles, and we wish you every success.", 'See open roles', 'careers', 'bad'],
        ['not_qualified', 'Requirements not met', 'Update on your application: {job}', 'Thank you for applying',
            "Hello {first_name},\n\nThank you for applying for {job} at {company}.\n\nAfter review, your profile does not currently meet the requirements for this role.\n\n{message}\n\nWe would be glad to hear from you again when you have gained more experience in this area, and we encourage you to watch our careers page for other roles.", 'See open roles', 'careers', 'bad'],
        ['updated', 'General update on an application', 'An update on your application: {job}', 'There is an update',
            "Hello {first_name},\n\nThere is an update on your application for {job} ({code}).\n\n{message}\n\nYou can see the full details and your application timeline in your applicant portal.", 'View the update', 'portal', 'info'],
    ];
}

/* ------------------------------------------------------------------ application form controls */
/** Reads cf_* / hr_* rows straight from the settings table (setting() only knows the fixed DEFAULT_SETTINGS keys). */
function hr_cfg(string $k): string {
    static $c = null;
    if ($c === null) $c = array_column(q("SELECT k,v FROM settings WHERE k LIKE 'cf\\_%' ESCAPE '\\' OR k LIKE 'hr\\_%' ESCAPE '\\'"), 'v', 'k');
    return (string)($c[$k] ?? '');
}
function field_mode(string $k): string {
    $m = hr_cfg('cf_' . $k);
    return in_array($m, array_keys(FIELD_MODES), true) ? $m : (APP_FIELDS[$k][1] ?? 'optional');
}
function field_shown(string $k): bool { return field_mode($k) !== 'hidden'; }
function field_req(string $k): bool { return field_mode($k) === 'required'; }
function cover_min(): int { $v = hr_cfg('cf_cover_min'); return $v === '' ? 100 : max(0, min(1500, (int)$v)); }
/** Label text with the right marker: "Phone number *" or "Phone number (optional)". */
function field_label(string $k, ?string $label = null): string {
    return ($label ?? APP_FIELDS[$k][0]) . (field_req($k) ? ' *' : ' (optional)');
}
function http_url(string $u): string { return $u !== '' && !preg_match('~^https?://~i', $u) ? 'https://' . $u : $u; }
/**
 * Validates the configurable fields of the application form. $v is normalised in place; returns [field => message].
 * Used by apply.php and by the applicant's "update my application" form, so the rules are the same everywhere.
 */
function hr_validate_fields(array &$v): array {
    $er = [];
    foreach (['phone', 'linkedin', 'portfolio', 'location', 'expected_pay', 'available_from'] as $k) $v[$k] = line((string)($v[$k] ?? ''));
    $v['cover_letter'] = (string)($v['cover_letter'] ?? '');
    if (field_shown('phone')) {
        if ($v['phone'] === '') { if (field_req('phone')) $er['phone'] = 'Enter a valid phone number.'; }
        elseif (!preg_match('/^\+?[0-9][0-9 \-()]{6,18}$/', $v['phone'])) $er['phone'] = 'Enter a valid phone number.';
    } else $v['phone'] = '';
    if (field_shown('linkedin')) {
        $li = http_url($v['linkedin']);
        if ($li === '') { if (field_req('linkedin')) $er['linkedin'] = 'Enter the link to your LinkedIn profile (linkedin.com/in/your-name).'; }
        elseif (!filter_var($li, FILTER_VALIDATE_URL) || !preg_match('/(^|\.)linkedin\.com$/', strtolower((string)parse_url($li, PHP_URL_HOST)))) $er['linkedin'] = 'Enter the link to your LinkedIn profile (linkedin.com/in/your-name).';
        else $v['linkedin'] = mb_substr($li, 0, 250);
    } else $v['linkedin'] = '';
    if (field_shown('cover_letter')) {
        $n = mb_strlen(trim($v['cover_letter']));
        if ($n === 0 && field_req('cover_letter')) $er['cover_letter'] = 'Please tell us why you are the best fit' . (cover_min() ? ' (at least ' . cover_min() . ' characters).' : '.');
        elseif ($n > 0 && field_req('cover_letter') && $n < cover_min()) $er['cover_letter'] = 'Please tell us why you are the best fit (at least ' . cover_min() . ' characters).';
    } else $v['cover_letter'] = '';
    if (field_shown('portfolio')) {
        $u = http_url($v['portfolio']);
        if ($u === '') { if (field_req('portfolio')) $er['portfolio'] = 'Add a link to your portfolio or website.'; }
        elseif (!filter_var($u, FILTER_VALIDATE_URL) || !str_contains((string)parse_url($u, PHP_URL_HOST), '.')) $er['portfolio'] = 'Enter a valid link, e.g. https://yourname.com.';
        else $v['portfolio'] = mb_substr($u, 0, 250);
    } else $v['portfolio'] = '';
    if (field_shown('location')) { if ($v['location'] === '' && field_req('location')) $er['location'] = 'Tell us where you are based.'; } else $v['location'] = '';
    if (field_shown('expected_pay')) { if ($v['expected_pay'] === '' && field_req('expected_pay')) $er['expected_pay'] = 'Tell us your expected pay.'; } else $v['expected_pay'] = '';
    if (field_shown('available_from')) {
        $d = $v['available_from'];
        if ($d === '') { if (field_req('available_from')) $er['available_from'] = 'Choose the earliest date you could start.'; }
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !strtotime($d)) $er['available_from'] = 'Choose a valid date.';
    } else $v['available_from'] = '';
    return $er;
}

/* ------------------------------------------------------------------ emails */
function hr_mail_on(): bool { return hr_cfg('hr_mail_on') !== '0'; }
function hr_template_map(): array {
    return ['Received' => 'received', 'Under Review' => 'under_review', 'Shortlisted' => 'shortlisted', 'Interview' => 'interview', 'More Details Needed' => 'more_details',
            'Accepted' => 'accepted', 'Hired' => 'hired', 'Rejected' => 'rejected', 'Not Qualified' => 'not_qualified'];
}
function hr_vars(array $app, array $job, array $extra = []): array {
    $first = explode(' ', trim((string)($app['name'] ?? 'there')))[0] ?: 'there';
    $when = !empty($app['interview_at']) ? date('l, j F Y \a\t g:i A', strtotime($app['interview_at'])) : 'To be confirmed. We will contact you to agree a time.';
    $start = '';
    if (!empty($app['id'])) $start = (string)qv("SELECT start_date FROM onboardings WHERE application_id=?", [$app['id']]);
    return array_merge([
        'name' => (string)($app['name'] ?? ''), 'first_name' => $first, 'job' => (string)($job['title'] ?? ''), 'company' => (string)($job['company'] ?? 'Empre Technologies'),
        'code' => (string)($app['code'] ?? ''), 'status' => (string)($app['status'] ?? ''), 'message' => '',
        'interview_when' => $when, 'interview_info' => trim((string)($app['interview_info'] ?? '')) ?: 'We will share the meeting details with you shortly.',
        'start_date' => $start !== '' ? date('l, j F Y', strtotime($start)) : '',
    ], $extra);
}
/** Replaces {placeholders}. A paragraph that holds an empty optional placeholder ({message}, {start_date}) is removed. */
function hr_fill(string $text, array $vars): string {
    $out = [];
    foreach (preg_split('/\R{2,}/', trim($text)) as $para) {
        $drop = false;
        $para = preg_replace_callback('/\{([a-z_]+)\}/', function ($m) use ($vars, &$drop) {
            $val = (string)($vars[$m[1]] ?? '');
            if ($val === '' && in_array($m[1], ['message', 'start_date'], true)) $drop = true;
            return $val;
        }, $para);
        if (!$drop) $out[] = trim($para);
    }
    return implode("\n\n", $out);
}
function hr_tone_colors(string $tone): array {
    return match ($tone) { 'good' => ['#E3F4E8', '#1E6B3A'], 'warn' => ['#FFE9D0', '#B85A00'], 'bad' => ['#F2E4E2', '#8A3A30'], default => ['#E3EEFB', '#1D5FA8'] };
}
/** Branded, table-based HTML email (works in Gmail, Outlook and phones). */
function hr_mail_html(string $headline, string $text, string $btnLabel, string $btnUrl, string $tone, string $pill, string $ref = ''): string {
    [$pbg, $pfg] = hr_tone_colors($tone);
    $paras = '';
    foreach (preg_split('/\R{2,}/', trim($text)) as $p) $paras .= '<p style="margin:0 0 16px;font-size:15.5px;line-height:1.65;color:#4B4747">' . nl2br(e($p), false) . '</p>';
    $logo = e(site_url('assets/img/logo-full.png'));
    $btn = $btnLabel !== '' ? '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:26px 0 8px"><tr><td style="background:#F77F00;border-radius:10px"><a href="' . e($btnUrl) . '" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none">' . e($btnLabel) . ' &rarr;</a></td></tr></table>' : '';
    $refRow = $ref !== '' ? '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:22px"><tr><td style="background:#FFF6EC;border-radius:10px;padding:12px 16px;font-size:13px;color:#7a7473">Reference: <b style="color:#1A1717">' . e($ref) . '</b></td></tr></table>' : '';
    return '<!doctype html><html><body style="margin:0;padding:0;background:#F6F3F0"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F6F3F0"><tr><td align="center" style="padding:28px 12px">'
        . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;font-family:Arial,Helvetica,sans-serif">'
        . '<tr><td style="height:6px;background:#F77F00;font-size:0;line-height:0">&nbsp;</td></tr>'
        . '<tr><td style="padding:26px 34px 6px"><img src="' . $logo . '" alt="Empre Technologies" height="46" style="height:46px;display:block;border:0"></td></tr>'
        . '<tr><td style="padding:14px 34px 4px"><span style="display:inline-block;background:' . $pbg . ';color:' . $pfg . ';font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:5px 12px;border-radius:999px">' . e($pill) . '</span>'
        . '<h1 style="margin:14px 0 14px;font-size:26px;line-height:1.25;color:#1A1717">' . e($headline) . '</h1></td></tr>'
        . '<tr><td style="padding:0 34px 30px">' . $paras . $btn . $refRow . '</td></tr>'
        . '<tr><td style="background:#373434;padding:20px 34px;font-size:12.5px;line-height:1.6;color:#cfc9c4"><b style="color:#ffffff">Empre Technologies Limited</b><br>Technology that empowers businesses to grow.<br>'
        . '<a href="' . e(site_url('')) . '" style="color:#F77F00;text-decoration:none">empretechnologies.com.ng</a> &nbsp;·&nbsp; ' . e(careers_inbox()) . '</td></tr>'
        . '</table><p style="font-family:Arial,sans-serif;font-size:11.5px;color:#9a9391;margin:14px 0 0">You are receiving this because you applied for a role at Empre Technologies.</p></td></tr></table></body></html>';
}
function hr_send_html(string $to, string $subject, string $html, string $text): bool {
    $b = 'empre_' . bin2hex(random_bytes(8));
    $h = ['From' => 'Empre Technologies Careers <' . FROM_EMAIL . '>', 'Reply-To' => careers_inbox(), 'MIME-Version' => '1.0', 'Content-Type' => 'multipart/alternative; boundary="' . $b . '"'];
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
          . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "--$b--";
    return @mail($to, '=?UTF-8?B?' . base64_encode(line($subject)) . '?=', $body, $h);
}
function hr_log_mail(string $to, string $subject, string $tpl, ?int $appId, string $status, string $html, string $text): int {
    return run("INSERT INTO mail_log(to_email,subject,template,application_id,status,body_html,body_text,sent_by,created_at) VALUES(?,?,?,?,?,?,?,?,?)",
        [$to, $subject, $tpl, $appId, $status, $html, $text, $_SESSION['admin']['name'] ?? ($_SESSION['applicant']['name'] ?? 'System'), now()]);
}
/** Renders a template for an application. Returns [subject, html, text] or null when the template does not exist. */
function hr_render(string $key, array $app, array $job, array $extra = [], ?array $tpl = null): ?array {
    $tpl = $tpl ?? q1("SELECT * FROM mail_templates WHERE k=?", [$key]);
    if (!$tpl) return null;
    $vars = hr_vars($app, $job, $extra);
    $subject = line(hr_fill((string)$tpl['subject'], $vars));
    $text = hr_fill((string)$tpl['body'], $vars);
    $url = $tpl['link'] === 'careers' ? site_url('careers.php') : site_url(!empty($app['id']) ? 'portal-app.php?id=' . $app['id'] : 'portal.php');
    $html = hr_mail_html(hr_fill((string)$tpl['headline'], $vars), $text, (string)$tpl['button'], $url, (string)$tpl['tone'], $app['status'] ?? $tpl['name'], (string)($app['code'] ?? ''));
    $plain = $text . ($tpl['button'] ? "\n\n" . $tpl['button'] . ': ' . $url : '') . "\n\nEmpre Technologies Careers\n" . site_url('');
    return [$subject, $html, $plain, $tpl];
}
/** Sends the applicant the email for $key (if that template and the master switch are on) and records it in the email log. */
function hr_notify(string $key, array $app, array $job, array $extra = []): string {
    if (!hr_mail_on()) return 'off';
    $r = hr_render($key, $app, $job, $extra);
    if (!$r || !(int)$r[3]['enabled']) return 'off';
    [$subject, $html, $text] = $r;
    $ok = hr_send_html((string)$app['email'], $subject, $html, $text);
    hr_log_mail((string)$app['email'], $subject, $key, (int)($app['id'] ?? 0) ?: null, $ok ? 'sent' : 'failed', $html, $text);
    return $ok ? 'sent' : 'failed';
}
function hr_resend(int $logId): string {
    $m = q1("SELECT * FROM mail_log WHERE id=?", [$logId]);
    if (!$m) return 'missing';
    $ok = hr_send_html((string)$m['to_email'], (string)$m['subject'], (string)$m['body_html'], (string)$m['body_text']);
    run("UPDATE mail_log SET status=?, created_at=? WHERE id=?", [$ok ? 'sent' : 'failed', now(), $logId]);
    return $ok ? 'sent' : 'failed';
}
function hr_sample(): array {
    return [['id' => 0, 'name' => 'Ngozi Adeyemi', 'email' => 'ngozi@example.com', 'code' => 'APP-2026-0042', 'status' => 'Under Review',
             'interview_at' => date('Y-m-d H:i:s', strtotime('+3 days 10:00')), 'interview_info' => 'Google Meet: https://meet.google.com/abc-defg-hij (about 30 minutes).'],
            ['title' => 'Sales and Onboarding Lead', 'company' => 'Conversa AI']];
}
function hr_notice(string $res): string {
    return match ($res) { 'sent' => ' The applicant was emailed.', 'failed' => ' The email could not be sent (see Email log, you can resend it).', default => '' };
}

/* ------------------------------------------------------------------ statuses */
function hr_status_event(string $set, string $note): string {
    $n = $note !== '' ? ' ' . $note : '';
    return match ($set) {
        'Under Review' => 'Your application is now under review.' . $n,
        'Shortlisted'  => 'Good news: you have been shortlisted.' . $n,
        'Interview'    => 'You have been invited to an interview.' . $n,
        'More Details Needed' => 'More details requested: ' . $note,
        'Accepted'     => 'Your application was accepted.' . $n,
        'Hired'        => 'Welcome to the team! Your onboarding checklist is ready.' . $n,
        'Rejected'     => 'Your application was not successful this time.' . $n,
        'Not Qualified' => 'Your profile does not currently meet the requirements for this role.' . $n,
        default        => 'Status changed to ' . $set . '.' . $n,
    };
}
/**
 * The ONE place an application changes status: updates it, writes the applicant timeline, logs the activity,
 * starts onboarding when someone is hired and sends the matching email. Returns 'sent' | 'failed' | 'off' | 'skipped'.
 * $a is the application row joined with jtitle/jcompany.
 */
function hr_set_status(array $a, string $set, string $note, string $actor, bool $notify = true, ?string $start = null): string {
    $id = (int)$a['id']; $note = trim($note);
    $unlock = $set === 'More Details Needed' ? 1 : 0;
    $decided = ['Accepted', 'Rejected', 'Not Qualified', 'Hired'];
    run("UPDATE applications SET status=?, edit_unlocked=?, info_request=CASE WHEN ?='More Details Needed' THEN ? ELSE info_request END,
         decision_note=CASE WHEN ? IN ('Accepted','Rejected','Not Qualified','Hired') THEN ? ELSE decision_note END, hired_at=CASE WHEN ?='Hired' THEN ? ELSE hired_at END, reviewed_by=?, updated_at=? WHERE id=?",
        [$set, $unlock, $set, $note, $set, $note, $set, now(), $actor, now(), $id]);
    app_event($id, 'status', hr_status_event($set, $note), $actor);
    log_act('application', $id, 'Status: ' . $a['status'] . ' → ' . $set);
    if ($set === 'Hired') hr_start_onboarding($id, $start, $actor);
    if (!$notify || !($tpl = hr_template_map()[$set] ?? null)) return 'skipped';
    $fresh = q1("SELECT * FROM applications WHERE id=?", [$id]);
    return hr_notify($tpl, $fresh, ['title' => $a['jtitle'], 'company' => $a['jcompany']], ['message' => $note]);
}

/* ------------------------------------------------------------------ onboarding */
function hr_start_onboarding(int $appId, ?string $start = null, string $actor = 'System'): int {
    $existing = (int)qv("SELECT id FROM onboardings WHERE application_id=?", [$appId]);
    $start = $start && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ? $start : null;
    if ($existing) { if ($start) run("UPDATE onboardings SET start_date=? WHERE id=?", [$start, $existing]); return $existing; }
    $oid = run("INSERT INTO onboardings(application_id,status,start_date,created_at) VALUES(?,?,?,?)", [$appId, 'In progress', $start, now()]);
    foreach (q("SELECT * FROM onboarding_template WHERE active=1 ORDER BY sort,id") as $t)
        run("INSERT INTO onboarding_tasks(onboarding_id,title,description,category,owner,due_date,needs_file,sort) VALUES(?,?,?,?,?,?,?,?)",
            [$oid, $t['title'], $t['description'], $t['category'], $t['owner'], $start ? date('Y-m-d', strtotime($start . ' ' . ((int)$t['due_days'] >= 0 ? '+' : '') . (int)$t['due_days'] . ' days')) : null, $t['needs_file'], $t['sort']]);
    app_event($appId, 'onboarding', 'Your onboarding checklist was created. Open it from your portal to get started.', $actor);
    log_act('application', $appId, 'Onboarding started');
    return $oid;
}
/** [done, total] */
function hr_onb_progress(int $oid): array {
    $r = q1("SELECT COUNT(*) t, COALESCE(SUM(done),0) d FROM onboarding_tasks WHERE onboarding_id=?", [$oid]);
    return [(int)$r['d'], (int)$r['t']];
}
function hr_progress_bar(int $done, int $total): string {
    $p = $total ? (int)round($done / $total * 100) : 0;
    return '<div class="prog"><span style="width:' . $p . '%"></span></div><small class="muted">' . $done . ' of ' . $total . ' tasks · ' . $p . '%</small>';
}
