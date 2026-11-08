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

## Staff, access levels and tasks
Create staff in **Admin > Staff & access** (Super Admin only). Each person has a **job title** (CEO, CTO, CIO, Manager, Team Lead,
Operation Lead, Operation, Sales, Facilitator: labels only) and an **access level** (what they can do):

| Access level | Can do | Cannot do |
|---|---|---|
| **Super Admin** | Everything, including all deletes, staff, settings, assigning tasks | — |
| **Level 1 · Administrator** | Manage students, payments, contacts, courses (create/edit) | Delete anything; settings; staff; assign tasks |
| **Level 2 · Billing Officer** | View students (read-only); create invoices, record/confirm payments, view receipts | Edit students/contacts/courses; delete anything |
| **Level 3 · Intake Officer** | Add new students | Billing, viewing students, and all other admin |

To change what a level can do, edit `ROLE_PERMS` at the top of `lib/admin_ui.php`.
Every staff member can always see and update **their own tasks**, whatever their level.

**Tasks:** every new enrolment automatically creates a task "Review application and prepare invoice" (**Not Assigned**).
The Super Admin assigns it to a staff member (**In Progress**); unassigning returns it to **Not Assigned**. The assignee posts
updates as comments and marks it **Completed** when done. The Super Admin can also add more tasks to any student, set due dates,
re-open or reassign. Assignees get an email when a task is assigned (needs working `mail()`).

**Upgrading an existing install:** upload the new files over the old ones. The database upgrades itself on first load, and the
admin account(s) you already have become **Super Admin**. Then add your staff.

## Security (important)
* `data/` holds the database. It is blocked by `data/.htaccess` on Apache/LiteSpeed. On Nginx — or to be safest anywhere —
  move the file outside `public_html` and change `DB_PATH` in `config.php` (e.g. `/home/youruser/empre-data/empre.sqlite`).
  Admin > Settings warns you if the file is still downloadable.
* Back up the database file regularly (download `data/*.sqlite`).
* Use HTTPS. Use a strong admin password (10+ characters).

## What's in it
Public: index.php, about.php, services.php, contact.php, training.php, course.php, enroll.php, enroll-success.php (old .html addresses redirect via the root .htaccess).
Admin (/admin): Dashboard, Contacts (status + next action), Students (profile, application, plan, invoices, payments), Courses (add/edit/hide), **Services (Super Admin only: add/edit/hide/delete service areas and services)**, Staff, Tasks, Settings.
Student ID format EMP-YYYY-NNNN · Invoices INV-YYYY-NNNN · Receipts REC-YYYY-NNNN (receipt is created when a payment is Confirmed).

## Services are managed in the admin
Admin > Services controls what appears on Home, Our Services, the footer and the contact form's service list. No code or re-upload needed.
The first time the site runs it loads the portfolio from the Enhanced Company Profile; after that the Super Admin owns it.

## Editing brand copy (tagline, positioning, values, process)
Change the text in `build.py`, run `python3 build.py`, re-upload. Hero images: `assets/img/hero-1..5.svg`.
Social links: replace the "#" URLs in the SOCIAL list in `build.py`.

## Local preview
`php -S localhost:8000` in this folder, then open http://localhost:8000

## Upgrading an existing live site to this version (services in the admin)
1. Upload everything (keep your existing `data/` folder and `config.php` values; do not overwrite your database).
2. Delete the old `index.html`, `about.html`, `services.html`, `contact.html` from the live site. The new `.php` pages replace them and the root `.htaccess` forwards the old addresses.
3. Open any page once. The new services tables are created and filled automatically, then sign in as Super Admin > Services.
