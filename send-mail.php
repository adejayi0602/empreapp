<?php
/**
 * Empre Technologies — contact form handler
 * Sends every enquiry to the address below. Needs PHP hosting with mail() enabled
 * (standard on cPanel / shared hosting). For SMTP, see README.md.
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';   // TO/FROM addresses are set in config.php (NOTIFY_EMAIL / FROM_EMAIL)

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out(bool $ok, int $code = 200, string $error = ''): void {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(false, 405, 'Method not allowed');

// Honeypot: bots fill this hidden field, people don't.
if (!empty($_POST['website'])) out(true);

// Basic rate limit: one message per 20 seconds per session.
if (isset($_SESSION['last_sent']) && time() - (int)$_SESSION['last_sent'] < 20) out(false, 429, 'Please wait a moment before sending again.');

// Strip CR/LF from single-line values to block header injection.
function clean(string $v, int $max): string {
    $v = trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v));
    return mb_substr($v, 0, $max);
}

$name    = clean((string)($_POST['name'] ?? ''), 100);
$email   = clean((string)($_POST['email'] ?? ''), 150);
$phone   = clean((string)($_POST['phone'] ?? ''), 40);
$service = clean((string)($_POST['service'] ?? ''), 80);
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 5000));

if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
    out(false, 422, 'Please complete all required fields.');
}

// 1) Save the enquiry so it shows in Admin > Contacts (never lose a lead)
$id = run("INSERT INTO contacts(name,email,phone,service,message,status,created_at,updated_at) VALUES(?,?,?,?,?,'New',?,?)",
    [$name, $email, $phone, $service, $message, now(), now()]);

// 2) Email a copy to the team
$subject = 'New website enquiry' . ($service !== '' ? " — $service" : '') . " — $name";
$body = "You have a new message from the Empre Technologies website.\n\n"
      . "Name:    $name\nEmail:   $email\nPhone:   " . ($phone !== '' ? $phone : '—') . "\nService: " . ($service !== '' ? $service : '—') . "\n"
      . "-----------------------------------------\n$message\n-----------------------------------------\n"
      . "Manage it in the admin dashboard: Contacts #$id\n";
send_mail(NOTIFY_EMAIL, $subject, $body, "$name <$email>");   // a mail failure does not lose the saved enquiry

$_SESSION['last_sent'] = time();
out(true);
