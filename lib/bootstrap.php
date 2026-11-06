<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('empre_sid');
    session_start();
}

/* ---------------------------------------------------------------- helpers */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function post(string $k, int $max = 255): string {
    $v = trim((string)($_POST[$k] ?? ''));
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return mb_substr($v, 0, $max);
}
function line(string $v): string { return trim(preg_replace('/[\r\n]+/', ' ', $v)); }
function dt(?string $s, string $fmt = 'd M Y'): string { return $s ? date($fmt, strtotime($s)) : '—'; }
function now(): string { return date('Y-m-d H:i:s'); }
function slugify(string $s): string {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? $s : 'course';
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = (string)($_POST['_csrf'] ?? '');
    if ($t === '' || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}
function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }

/* --------------------------------------------------------------- database */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    migrate($pdo);
    return $pdo;
}
function q(string $sql, array $p = []): array { $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function q1(string $sql, array $p = []): ?array { $s = db()->prepare($sql); $s->execute($p); $r = $s->fetch(); return $r ?: null; }
function qv(string $sql, array $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function run(string $sql, array $p = []): int { $s = db()->prepare($sql); $s->execute($p); return (int)db()->lastInsertId(); }

function migrate(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS admins(id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT);
    CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE IF NOT EXISTS courses(
        id INTEGER PRIMARY KEY, slug TEXT NOT NULL UNIQUE, title TEXT NOT NULL, icon TEXT DEFAULT 'code',
        summary TEXT, description TEXT, outline TEXT, requirements TEXT,
        duration TEXT, mode TEXT, level TEXT, fee REAL DEFAULT 0, start_info TEXT,
        active INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, created_at TEXT);
    CREATE TABLE IF NOT EXISTS students(
        id INTEGER PRIMARY KEY, student_code TEXT NOT NULL UNIQUE, name TEXT NOT NULL, age INTEGER, sex TEXT, location TEXT,
        email TEXT NOT NULL, phone TEXT NOT NULL, course_id INTEGER, course_title TEXT,
        status TEXT DEFAULT 'Applied', plan TEXT DEFAULT 'Not set', fee REAL DEFAULT 0, discount REAL DEFAULT 0,
        next_action TEXT, notes TEXT, follow_up TEXT, created_at TEXT, updated_at TEXT);
    CREATE TABLE IF NOT EXISTS invoices(
        id INTEGER PRIMARY KEY, invoice_no TEXT NOT NULL UNIQUE, student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
        description TEXT, amount REAL NOT NULL, issued_on TEXT, due_date TEXT, status TEXT DEFAULT 'Open', created_at TEXT);
    CREATE TABLE IF NOT EXISTS payments(
        id INTEGER PRIMARY KEY, student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE, invoice_id INTEGER REFERENCES invoices(id) ON DELETE SET NULL,
        amount REAL NOT NULL, method TEXT, reference TEXT, paid_on TEXT, status TEXT DEFAULT 'Pending',
        receipt_no TEXT UNIQUE, note TEXT, created_at TEXT, confirmed_at TEXT, confirmed_by TEXT);
    CREATE TABLE IF NOT EXISTS contacts(
        id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, service TEXT, message TEXT,
        status TEXT DEFAULT 'New', next_action TEXT, notes TEXT, follow_up TEXT, created_at TEXT, updated_at TEXT);
    CREATE TABLE IF NOT EXISTS activity(id INTEGER PRIMARY KEY, entity TEXT, entity_id INTEGER, text TEXT, admin TEXT, created_at TEXT);
    CREATE TABLE IF NOT EXISTS tasks(
        id INTEGER PRIMARY KEY, student_id INTEGER REFERENCES students(id) ON DELETE CASCADE, title TEXT NOT NULL, details TEXT,
        assigned_to INTEGER REFERENCES admins(id) ON DELETE SET NULL, status TEXT DEFAULT 'Not Assigned', due_date TEXT,
        created_by TEXT, created_at TEXT, assigned_at TEXT, updated_at TEXT, completed_at TEXT, completed_by TEXT);
    CREATE TABLE IF NOT EXISTS task_comments(
        id INTEGER PRIMARY KEY, task_id INTEGER NOT NULL REFERENCES tasks(id) ON DELETE CASCADE, admin_id INTEGER, author TEXT, body TEXT NOT NULL, created_at TEXT);
    CREATE INDEX IF NOT EXISTS ix_task_assignee ON tasks(assigned_to, status);
    CREATE INDEX IF NOT EXISTS ix_task_student ON tasks(student_id);
    CREATE INDEX IF NOT EXISTS ix_task_comments ON task_comments(task_id);
    CREATE INDEX IF NOT EXISTS ix_act ON activity(entity, entity_id);
    CREATE INDEX IF NOT EXISTS ix_stu_course ON students(course_id);
    CREATE INDEX IF NOT EXISTS ix_pay_inv ON payments(invoice_id);
    ");
    // Upgrade older installs: staff roles. Existing admins become Super Admins.
    $cols = array_column($pdo->query("PRAGMA table_info(admins)")->fetchAll(), 'name');
    foreach (['title' => "TEXT DEFAULT ''", 'role' => "TEXT DEFAULT 'super_admin'", 'active' => 'INTEGER DEFAULT 1', 'last_login' => 'TEXT'] as $c => $def)
        if (!in_array($c, $cols, true)) $pdo->exec("ALTER TABLE admins ADD COLUMN $c $def");
    if (!$pdo->query("SELECT 1 FROM settings WHERE k='seeded'")->fetch()) {
        seed_courses($pdo);
        $pdo->exec("INSERT OR IGNORE INTO settings(k,v) VALUES('seeded','1')");
    }
}

function seed_courses(PDO $pdo): void {
    $c = [
     ['Front End Development','code','Learn to design and build fast, responsive websites and web apps with HTML, CSS, JavaScript and React.',
      "This hands-on programme takes you from the fundamentals of the web to building modern, responsive interfaces with React and TypeScript. You will work on real projects and finish with a portfolio you can show to employers and clients.",
      "HTML5 and semantic markup\nCSS3, Flexbox, Grid and responsive design\nJavaScript (ES6+) fundamentals\nGit and GitHub workflow\nReact and TypeScript essentials\nWorking with APIs and application state\nDeployment and a capstone portfolio project",
      "A laptop with internet access\nNo prior coding experience required",'12 weeks','Virtual or In-person','Beginner'],
     ['Cloud Computing','cloud','Understand cloud platforms and learn to deploy, secure and manage services on Microsoft Azure and beyond.',
      "Cloud skills are in demand in every industry. This course covers core cloud concepts and gives you practical, lab-based experience deploying and managing cloud services, with a Microsoft focus and an introduction to other major platforms.",
      "Cloud fundamentals and service models\nMicrosoft Azure core services\nVirtual machines, storage and networking\nIdentity and access with Entra ID\nMicrosoft 365 and cloud productivity\nCost management and governance\nHands-on labs and certification preparation",
      "A laptop with internet access\nBasic computer skills",'8 weeks','Virtual or In-person','Beginner'],
     ['Cyber Security','lock','Protect people, devices and data. Learn the threats, the tools and the practices that keep organisations safe.',
      "Learn how attackers think and how defenders respond. This programme combines security awareness, endpoint protection and hands-on practice, preparing you for entry-level security roles and industry certifications.",
      "Security fundamentals and the threat landscape\nMalware, phishing and social engineering\nNetwork security essentials\nEndpoint protection with Intune and Defender\nIdentity and access management\nSecurity policies and awareness\nIncident response basics\nIntroduction to ethical hacking and certification prep",
      "A laptop with internet access\nBasic computer and networking knowledge is helpful",'10 weeks','Virtual or In-person','Beginner to Intermediate'],
     ['DevOps Engineering','refresh','Automate how software is built, tested and shipped using CI/CD, containers and infrastructure as code.',
      "Bridge development and operations. You will learn the practices and tools used by modern engineering teams to deliver software reliably and quickly, finishing with an end-to-end deployment project.",
      "Linux and the command line\nGit workflows and collaboration\nCI/CD pipelines\nDocker and containers\nKubernetes fundamentals\nInfrastructure as Code with Terraform\nMonitoring and logging\nCloud deployment capstone project",
      "A laptop with internet access\nBasic programming or IT knowledge",'12 weeks','Virtual or In-person','Intermediate'],
     ['Product Design','pen','Design digital products people love: research, wireframes, UI design and prototypes in Figma.',
      "Learn the full product design process, from understanding users to shipping polished interfaces. You will practise research, wireframing, visual design and prototyping, and build a case study for your portfolio.",
      "Design thinking and UX research\nPersonas and user journeys\nWireframing and information architecture\nUI principles: layout, typography and colour\nFigma and interactive prototyping\nDesign systems\nUsability testing\nPortfolio case study",
      "A laptop with internet access\nNo prior design experience required",'8 weeks','Virtual or In-person','Beginner'],
    ];
    $st = $pdo->prepare("INSERT INTO courses(slug,title,icon,summary,description,outline,requirements,duration,mode,level,fee,active,sort,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,0,1,?,?)");
    foreach ($c as $i => $r) $st->execute([slugify($r[0]), $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $r[8], $i + 1, now()]);
}

/* --------------------------------------------------------------- settings */
function settings(): array {
    static $s = null;
    if ($s === null) {
        $s = DEFAULT_SETTINGS;
        foreach (q("SELECT k,v FROM settings") as $r) if (array_key_exists($r['k'], $s)) $s[$r['k']] = $r['v'];
    }
    return $s;
}
function setting(string $k): string { return (string)(settings()[$k] ?? ''); }
function save_setting(string $k, string $v): void { run("INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v", [$k, $v]); }
function money($n): string { return setting('currency') . number_format((float)$n, 2); }

/* ------------------------------------------------------------------- misc */
function log_act(string $entity, int $id, string $text): void {
    run("INSERT INTO activity(entity,entity_id,text,admin,created_at) VALUES(?,?,?,?,?)", [$entity, $id, $text, $_SESSION['admin']['name'] ?? 'System', now()]);
}
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool {
    $h = ['From' => 'Empre Technologies <' . FROM_EMAIL . '>', 'MIME-Version' => '1.0', 'Content-Type' => 'text/plain; charset=UTF-8'];
    if ($replyTo) $h['Reply-To'] = line($replyTo);
    return @mail($to, '=?UTF-8?B?' . base64_encode(line($subject)) . '?=', $body, $h);
}
/** Next sequential code such as EMP-2026-0001 */
function next_code(string $prefix, string $table, string $col): string {
    $y = date('Y');
    $max = qv("SELECT MAX($col) FROM $table WHERE $col LIKE ?", ["$prefix-$y-%"]);
    $n = $max ? ((int)explode('-', (string)$max)[2] + 1) : 1;
    return sprintf('%s-%s-%04d', $prefix, $y, $n);
}
/** Insert a row that has a generated unique code, retrying on collisions. */
function insert_with_code(string $prefix, string $table, string $col, callable $insert): string {
    for ($i = 0; $i < 6; $i++) {
        $code = next_code($prefix, $table, $col);
        try { $insert($code); return $code; }
        catch (PDOException $ex) { if (strpos($ex->getMessage(), 'UNIQUE') === false) throw $ex; }
    }
    throw new RuntimeException('Could not generate a unique code');
}

/* ----------------------------------------------------------------- courses */
function active_courses(): array { return q("SELECT * FROM courses WHERE active=1 ORDER BY sort, title"); }
function course_by_slug(string $slug): ?array { return q1("SELECT * FROM courses WHERE slug=? AND active=1", [$slug]); }
function lines(?string $t): array { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$t)), 'strlen')); }

/* ---------------------------------------------------------------- students */
const SEXES = ['Male', 'Female', 'Prefer not to say'];
const TITLES = ['CEO', 'CTO', 'CIO', 'Manager', 'Team Lead', 'Operation Lead', 'Operation', 'Sales', 'Facilitator'];
const TASK_STATUSES = ['Not Assigned', 'In Progress', 'Completed'];
const STUDENT_STATUSES = ['Applied', 'Under review', 'Approved', 'Enrolled', 'Completed', 'Withdrawn'];
const PLANS = ['Not set', 'Full payment', 'Installments'];

function create_student(array $d): array {
    $course = q1("SELECT * FROM courses WHERE id=?", [(int)$d['course_id']]);
    if (!$course) throw new InvalidArgumentException('Course not found');
    $id = 0;
    $code = insert_with_code('EMP', 'students', 'student_code', function ($code) use ($d, $course, &$id) {
        $id = run("INSERT INTO students(student_code,name,age,sex,location,email,phone,course_id,course_title,status,plan,fee,created_at,updated_at)
                   VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$code, $d['name'], (int)$d['age'], $d['sex'], $d['location'], $d['email'], $d['phone'], $course['id'], $course['title'],
             $d['status'] ?? 'Applied', 'Not set', $course['fee'], now(), now()]);
    });
    log_act('student', $id, 'Application received for ' . $course['title'] . ' (' . $code . ')');
    // Every new student gets a starter task so nothing is missed. The Super Admin assigns it to a staff member.
    create_task($id, 'Review application and prepare invoice', 'New enrolment for ' . $course['title'] . '. Verify the applicant details, confirm the fee, and prepare the invoice.', null, '', 'System');
    return ['id' => $id, 'code' => $code, 'course' => $course];
}

function student_totals(array $s): array {
    $due   = max(0, (float)$s['fee'] - (float)$s['discount']);
    $inv   = (float)qv("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE student_id=? AND status<>'Cancelled'", [$s['id']]);
    $paid  = (float)qv("SELECT COALESCE(SUM(amount),0) FROM payments WHERE student_id=? AND status='Confirmed'", [$s['id']]);
    $bal   = $due - $paid;
    $label = $paid <= 0 ? 'Unpaid' : ($bal > 0.004 ? 'Partial' : 'Paid');
    if ($due <= 0 && $paid <= 0) $label = 'No fee set';
    return ['due' => $due, 'invoiced' => $inv, 'paid' => $paid, 'balance' => $bal, 'label' => $label];
}
function invoice_state(array $i): array {
    $paid = (float)qv("SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=? AND status='Confirmed'", [$i['id']]);
    $bal = (float)$i['amount'] - $paid;
    if ($i['status'] === 'Cancelled') $label = 'Cancelled';
    elseif ($bal <= 0.004) $label = 'Paid';
    elseif ($paid > 0) $label = 'Partial';
    elseif ($i['due_date'] && $i['due_date'] < date('Y-m-d')) $label = 'Overdue';
    else $label = 'Unpaid';
    return ['paid' => $paid, 'balance' => $bal, 'label' => $label];
}


/* ------------------------------------------------------------------ tasks
 * Status rules:  assigned to someone -> "In Progress" · unassigned -> "Not Assigned" · finished -> "Completed"
 */
function create_task(int $studentId, string $title, string $details = '', ?int $assignee = null, string $due = '', string $by = 'System'): int {
    $id = run("INSERT INTO tasks(student_id,title,details,assigned_to,status,due_date,created_by,created_at,assigned_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)",
        [$studentId, $title, $details, $assignee, $assignee ? 'In Progress' : 'Not Assigned', $due, $by, now(), $assignee ? now() : null, now()]);
    log_act('task', $id, 'Task created' . ($assignee ? ' and assigned to ' . admin_label($assignee) : ' (not assigned)'));
    return $id;
}
function task_assign(int $taskId, ?int $adminId): void {
    $before = q1("SELECT * FROM tasks WHERE id=?", [$taskId]);
    if (!$before) return;
    if ($adminId) {
        run("UPDATE tasks SET assigned_to=?, status='In Progress', assigned_at=?, completed_at=NULL, completed_by=NULL, updated_at=? WHERE id=?", [$adminId, now(), now(), $taskId]);
        log_act('task', $taskId, 'Assigned to ' . admin_label($adminId) . ' — status: In Progress');
    } else {
        run("UPDATE tasks SET assigned_to=NULL, status='Not Assigned', assigned_at=NULL, completed_at=NULL, completed_by=NULL, updated_at=? WHERE id=?", [now(), $taskId]);
        log_act('task', $taskId, 'Unassigned — status: Not Assigned');
    }
}
function admin_label(?int $id): string {
    $a = $id ? q1("SELECT name,title FROM admins WHERE id=?", [$id]) : null;
    return $a ? $a['name'] . ($a['title'] ? ' (' . $a['title'] . ')' : '') : 'Unknown';
}
