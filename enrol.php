<?php
/**
 * Empre Technologies — course enrolment handler
 *  1. validates the applicant
 *  2. generates a unique student ID  (EMP-<yy>-<0001, 0002 …>)
 *  3. saves the record to data/enrolments.csv  (for admin review / invoicing)
 *  4. emails the admin team and sends the applicant a confirmation
 * The record is saved BEFORE any email is attempted, so an enrolment is never lost
 * if the mail server is down.
 */
declare(strict_types=1);

const ADMIN_EMAIL = 'info@empretechnologies.com.ng';     // <- admin staff who prepare invoices
const FROM_EMAIL  = 'no-reply@empretechnologies.com.ng'; // <- an address on YOUR domain
const SITE_NAME   = 'Empre Technologies Training';
const ID_PREFIX   = 'EMP';
const COURSES     = ['Front End Development', 'Cloud Computing', 'Cyber Security', 'DevOps Engineering'];
const DATA_DIR    = __DIR__ . '/data';   // protected by data/.htaccess (move outside public_html if you can)

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out(array $p, int $code = 200): void { http_response_code($code); echo json_encode($p); exit; }
function fail(string $m, int $code = 422): void { out(['ok' => false, 'error' => $m], $code); }
function clean(string $v, int $max): string {
    return mb_substr(trim(preg_replace('/[\r\n\t]+/', ' ', $v)), 0, $max);
}
// Stop spreadsheet formula injection when admin opens the CSV in Excel.
function safe_cell(string $v): string { return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);
if (!empty($_POST['website'])) out(['ok' => true, 'student_id' => 'EMP-00-0000']); // honeypot: bots get a fake success

$name     = clean((string)($_POST['name'] ?? ''), 120);
$age      = (int)($_POST['age'] ?? 0);
$sex      = clean((string)($_POST['sex'] ?? ''), 20);
$location = clean((string)($_POST['location'] ?? ''), 150);
$email    = clean((string)($_POST['email'] ?? ''), 150);
$phone    = clean((string)($_POST['phone'] ?? ''), 30);
$course   = clean((string)($_POST['course'] ?? ''), 80);

if (mb_strlen($name) < 3)                              fail('Please enter your full name.');
if ($age < 10 || $age > 99)                            fail('Please enter a valid age.');
if (!in_array($sex, ['Male', 'Female'], true))         fail('Please select your sex.');
if (mb_strlen($location) < 2)                          fail('Please enter your location.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        fail('Please enter a valid email address.');
if (!preg_match('/^\+?[0-9 ()\-]{7,20}$/', $phone))    fail('Please enter a valid phone number.');
if (!in_array($course, COURSES, true))                 fail('Unknown course selected.');

if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0750, true)) fail('Server storage unavailable.', 500);
if (!is_file(DATA_DIR . '/.htaccess')) file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");

// ---- one critical section: duplicate check + ID generation + save ----
$lock = fopen(DATA_DIR . '/.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX)) fail('Server busy, please try again.', 503);

$csv = DATA_DIR . '/enrolments.csv';
$isNew = !is_file($csv) || filesize($csv) === 0;

// Reject a repeat enrolment (same email + same course).
if (!$isNew && ($h = fopen($csv, 'r'))) {
    fgetcsv($h);
    while (($r = fgetcsv($h)) !== false) {
        if (isset($r[8], $r[6]) && strcasecmp($r[6], $email) === 0 && $r[8] === $course) {
            fclose($h); flock($lock, LOCK_UN);
            fail('This email is already enrolled for ' . $course . '. Our team will contact you shortly.', 409);
        }
    }
    fclose($h);
}

// Sequential counter, restarting each year: EMP-26-0001
$year = date('y');
$cf = DATA_DIR . '/counter.txt';
$n = 0;
if (is_file($cf)) { [$y, $c] = array_pad(explode(':', trim((string)file_get_contents($cf))), 2, '0'); if ($y === $year) $n = (int)$c; }
$n++;
$studentId = sprintf('%s-%s-%04d', ID_PREFIX, $year, $n);

$fh = fopen($csv, 'a');
if (!$fh) { flock($lock, LOCK_UN); fail('Could not save enrolment.', 500); }
if ($isNew) fputcsv($fh, ['Student ID', 'Date', 'Name', 'Age', 'Sex', 'Location', 'Email', 'Phone', 'Course', 'Status']);
fputcsv($fh, [$studentId, date('Y-m-d H:i:s'), safe_cell($name), $age, $sex, safe_cell($location), $email, $phone, $course, 'Pending invoice']);
fclose($fh);
file_put_contents($cf, "$year:$n", LOCK_EX);
flock($lock, LOCK_UN); fclose($lock);

// ---- emails (best effort; the record is already saved) ----
function send(string $to, string $subject, string $body, string $replyTo = ''): bool {
    $h = ['From' => SITE_NAME . ' <' . FROM_EMAIL . '>', 'MIME-Version' => '1.0', 'Content-Type' => 'text/plain; charset=UTF-8'];
    if ($replyTo !== '') $h['Reply-To'] = $replyTo;
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $h);
}
send(ADMIN_EMAIL, "New enrolment $studentId — $course — $name",
    "A new student has enrolled. Please review and prepare the invoice.\n\n"
  . "Student ID: $studentId\nName:       $name\nAge:        $age\nSex:        $sex\nLocation:   $location\n"
  . "Email:      $email\nPhone:      $phone\nCourse:     $course\nDate:       " . date('D, d M Y H:i:s T') . "\n", "$name <$email>");
send($email, "Your Empre Technologies enrolment — $studentId",
    "Hello $name,\n\nThank you for enrolling for $course with Empre Technologies.\n\n"
  . "Your Student ID: $studentId\n\nOur team will review your enrolment and send your invoice and next steps to this email address.\n"
  . "Please quote your Student ID in any communication with us.\n\nEmpre Technologies Ltd\n" . ADMIN_EMAIL . "\n+234 810 370 2504\n", ADMIN_EMAIL);

out(['ok' => true, 'student_id' => $studentId, 'name' => $name, 'course' => $course]);
