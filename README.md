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

## Careers & applicant portal

**Public:** `careers.php` (open roles, search/filter), `job.php?j=slug` (full description), `apply.php?j=slug` (standard application form: name, email, phone, LinkedIn, CV upload, cover letter, optional role question, portal password).
**Applicant portal:** `portal-login.php`, `portal.php`, `portal-app.php?id=N`. Applicants track status and the job's status. They can edit/upload more **only** after an admin chooses "More details needed" (this unlocks editing once; sending an update puts the application back to Under Review and locks it again).
**Admin (Careers / Applications):** add, edit, publish, close/reopen and delete jobs; review applications (Accept, Reject, More details needed, Not qualified, Mark under review), add internal notes, reset an applicant's portal password, export jobs and applications to CSV (filters respected; spreadsheet-formula safe).
**Access:** Super Admin and Level 1 (Manager) can view/manage careers; only the Super Admin can delete jobs/applications. Billing and Intake have no access.
**Seed data:** the three Conversa AI roles are created on first run. Edit them in Admin → Careers. Set the careers notification inbox in Admin → Settings → Careers inbox.
**Uploads:** stored in `data/uploads/` (auto-created, deny-all `.htaccess`, served only through PHP after permission checks). Limit is 5 MB or your server's `upload_max_filesize`/`post_max_size`, whichever is lower. **Keep the `data/` folder when upgrading.**

## SEO & Visibility (Admin → SEO & Visibility)

One console for everything search engines, social networks and AI assistants see.
- **Overview:** health score, site-wide checks with "Fix →" links, and a live **site audit** (loads every page and checks H1, word count, image alt text, canonical, structured data, speed).
- **Pages:** title, description, focus keyword, share image, canonical and noindex for every page, with a live Google and WhatsApp/LinkedIn preview. Service, course and job pages are written automatically and can be overridden. (Level 1 can edit these; everything below is Super Admin only.)
- **Business & schema:** company description, address, hours, areas served and official profiles → published as Organization / ProfessionalService structured data, with a service catalogue.
- **Technical:** live address, www / HTTPS preference, block-all switch for test copies, AI-crawler switch, default share image, extra robots.txt rules, extra notes for `llms.txt`, **IndexNow** (instant notice to Bing, Yandex and others when you change content).
- **Tracking & verification:** Google/Bing/Yandex/Facebook/Pinterest verification (paste the code or the whole meta tag), GA4, Tag Manager, Meta Pixel, Microsoft Clarity, custom head/body code. Never loaded on admin, application or portal pages.
- **Redirects & 404s:** 301/302 redirect manager and a log of broken links with one-click fixes.
- **Visibility checklist:** Google Business Profile, Bing Places, LinkedIn, directories, reviews, etc. Profiles marked Live are added to the structured data.

Generated automatically: `sitemap.xml`, `robots.txt`, `llms.txt`, per-page canonical / Open Graph / Twitter cards, JSON-LD (Organization, WebSite, Breadcrumbs, Service, Course, JobPosting), one landing page per service (`service.php?s=…`), a branded 404 page.

**After uploading:** (1) fill the Business tab (address matters most for Google Maps), (2) verify the site in Google Search Console and Bing Webmaster Tools, paste the codes in Tracking, submit `sitemap.xml`, (3) press "Notify search engines now", (4) work through the Visibility checklist.
**Upgrade notes:** replace `.htaccess` (it adds the sitemap/robots rewrites, an error page and caching); keep `data/`. If `/sitemap.xml` shows "not found", use `/sitemap.php` and `/robots.php` (your host has no URL rewriting). If the site lives in a sub-folder, change `ErrorDocument 404 /404.php` in `.htaccess`. Turn on "Force HTTPS" only after the SSL certificate works.

## HR & Careers (mini HR system)

Everything lives under **Admin → HR & Careers**, grouped in the sidebar:

| Group | Screen | What it does |
|---|---|---|
| Recruitment | **HR overview** | KPIs, hiring funnel, “needs attention”, upcoming interviews, onboarding progress |
| | **Job posts** / **Applications** | Vacancies and the application list (CSV export includes the new fields) |
| | **Pipeline board** | Kanban of every applicant. Drag a card (or use “Move to…”) to change status; the applicant is emailed |
| Onboarding | **New hires** | Everyone hired, with progress; “Ready to onboard” lists accepted applicants |
| | **Onboarding checklist** | The template copied to every new hire (editable; changes affect future hires) |
| Settings | **Application form** | Set Phone, LinkedIn, CV, Cover letter, Portfolio, Location, Expected pay, Start date to Required / Optional / Hidden |
| | **Email templates** | Edit every automatic email, preview it, send a test, switch each one (or all) off |
| | **Email log** | Every email sent, its delivery result, a copy of what was sent, and Resend |

**Statuses:** Received → Under Review → Shortlisted → Interview → Accepted → Hired (plus More Details Needed, Rejected, Not Qualified).
All status changes go through `hr_set_status()` in `lib/hr.php`: it updates the application, writes the applicant timeline, logs staff activity,
starts onboarding on **Hired**, and emails the applicant.

**Emails** use PHP `mail()` (same as the rest of the site) and are sent as branded HTML with a plain-text fallback. If your host blocks `mail()`,
failures appear in the Email log and on the HR overview; applicants still see every update in their portal. Placeholders: `{first_name} {name} {job} {company} {code} {status} {message} {interview_when} {interview_info} {start_date}`.

**Onboarding:** hiring creates a checklist from the template (due dates are counted from the start date). Tasks owned by *Employee* appear in the new hire’s
applicant portal where they tick them off or upload documents (offer letter, ID, photo…). HR, IT and Manager tasks are ticked by staff in *New hires → Open*.

**Access:** Super Admin can change the form rules, email templates and checklist template (`careers.settings`). Level 1 can use the overview, pipeline,
applications, new hires and email log. Billing and Intake have no access.

Tests: `node hr.js` (97 checks) after a fresh database.
