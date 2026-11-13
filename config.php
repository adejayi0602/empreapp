<?php
/** Empre Technologies — site configuration. Edit these values, then upload. */
declare(strict_types=1);

// Database file (SQLite). Lives in /data which is blocked from the web by data/.htaccess.
const DB_PATH = __DIR__ . '/data/empre-7f3a9c21.sqlite';

// Where notifications go (new contact / new enrolment)
const NOTIFY_EMAIL = 'info@empretechnologies.com.ng';
// Sender address — use an address on YOUR domain so mail is not marked as spam
const FROM_EMAIL   = 'no-reply@empretechnologies.com.ng';

// Defaults for invoices/receipts (can be changed later in Admin > Settings)
const DEFAULT_SETTINGS = [
    'company_name'   => 'Empre Technologies Ltd',
    'company_email'  => 'info@empretechnologies.com.ng',
    'company_phone'  => '+234 813 964 5973',
    'company_web'    => 'empretechnologies.com.ng',
    'company_address'=> '',
    'currency'       => '₦',
    'bank_name'      => '',
    'account_name'   => 'Empre Technologies Ltd',
    'account_number' => '',
    'invoice_note'   => 'Please use your Student ID as the payment reference and send proof of payment to our email.',
];

date_default_timezone_set('Africa/Lagos');
