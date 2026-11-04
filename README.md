# Empre Technologies website + training & admin system

PHP 8+ site with a built-in SQLite database (no database setup needed).
Requirements: PHP 8.0+ with pdo_sqlite and mbstring (standard on cPanel), and mail() enabled for email.

## Install
1. Upload everything in this folder to `public_html`.
2. Open `https://yourdomain/admin/` — first visit asks you to create the admin account (this screen only works once).
3. Go to Admin > Settings: add your bank details (shown on invoices) and check company details.
4. Go to Admin > Courses: set the fee for each course (fees start at 0 = "Contact us") and review the seeded course content.
5. Edit `config.php`: set NOTIFY_EMAIL / FROM_EMAIL (use an address on your own domain).
6. Send a test contact message and a test enrolment, and confirm the emails arrive.

## Security (important)
* `data/` holds the database. It is blocked by `data/.htaccess` on Apache/LiteSpeed. On Nginx — or to be safest anywhere —
  move the file outside `public_html` and change `DB_PATH` in `config.php` (e.g. `/home/youruser/empre-data/empre.sqlite`).
  Admin > Settings warns you if the file is still downloadable.
* Back up the database file regularly (download `data/*.sqlite`).
* Use HTTPS. Use a strong admin password (10+ characters).

## What's in it
Public: index.html, about.html, services.html, contact.html (all static) + training.php, course.php, enroll.php, enroll-success.php.
Admin (/admin): Dashboard, Contacts (status + next action), Students (profile, application, plan, invoices, payments), Courses (add/edit/hide), Settings.
Student ID format EMP-YYYY-NNNN · Invoices INV-YYYY-NNNN · Receipts REC-YYYY-NNNN (receipt is created when a payment is Confirmed).

## Editing the static pages
Change content at the top of `build.py`, run `python3 build.py`, re-upload. Hero images: `assets/img/hero-1..5.svg`.
Social links: replace the "#" URLs in the SOCIAL list in `build.py`.

## Local preview
`php -S localhost:8000` in this folder, then open http://localhost:8000
