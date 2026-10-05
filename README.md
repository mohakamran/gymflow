<div align="center">

# 🏋️ GymFlow

### All-in-one gym management software: one workspace per gym

Members, memberships, check-ins, payments, invoices, classes, trainers, equipment, reports and a member portal, branded with each gym's logo and colors.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white)](https://alpinejs.dev)
[![Tests](https://img.shields.io/badge/tests-85%20passing-22c55e)](#-testing)
[![Databases](https://img.shields.io/badge/DB-SQLite%20%7C%20MySQL%20%7C%20PostgreSQL-4479A1?logo=mysql&logoColor=white)](#-database-setup)

![GymFlow dashboard](docs/screenshots/dashboard.png)

</div>

---

## 📚 Table of contents

1. [What is GymFlow?](#-what-is-gymflow)
2. [Installation](#-installation)
3. [Database setup: SQLite, MySQL, PostgreSQL](#-database-setup)
4. [Demo accounts](#-demo-accounts)
5. [Screenshots](#-screenshots)
6. [Features](#-features)
7. [Who can use it: roles & permissions](#-who-can-use-it-roles--permissions)
8. [Technologies used](#-technologies-used)
9. [Configuration](#-configuration)
10. [Background jobs & scheduler](#-background-jobs--scheduler)
11. [Testing](#-testing)
12. [Architecture](#-architecture)
13. [Project structure](#-project-structure)
14. [Deploying to production](#-deploying-to-production)
15. [Extending GymFlow](#-extending-gymflow)
16. [Troubleshooting](#-troubleshooting)
17. [License](#-license)

---

## 💡 What is GymFlow?

GymFlow is **multi-tenant SaaS software for gyms and fitness studios**, built with Laravel and Blade:

- **Gyms sign themselves up.** Each new gym gets a free trial and its own workspace.
- **Each gym is fully isolated.** One gym can never see another gym's data.
- **Each gym uses its own branding.** Its logo, colors, currency, timezone, tax rules and invoice settings appear throughout the app.
- **Everyone works in one app.** Owners, reception staff, trainers and members each see only what their role allows.

| For gym owners | For the front desk | For trainers | For members |
|---|---|---|---|
| Revenue, profit & growth dashboards | 2-second QR check-in & kiosk | Assigned members | Online class booking |
| Plans, pricing & tax | Sell & renew memberships | Workout plans | QR check-in pass |
| Team, equipment & expenses | Payments & invoices | Progress tracking | Invoices & payments |
| Reports with PDF/CSV export | Class bookings | Class roster | Workouts & progress |

---

## 🚀 Installation

### Step 1: Install the required tools

You need **PHP**, **Composer**, **Node.js** and **Git**. A database server is optional, because SQLite works out of the box.

| Tool | Version | Windows | macOS | Linux (Ubuntu/Debian) |
|---|---|---|---|---|
| **PHP** | 8.3 or newer | [Laravel Herd](https://herd.laravel.com) (includes Composer) | `brew install php` or [Herd](https://herd.laravel.com) | `sudo apt install php8.3 php8.3-{cli,sqlite3,mysql,pgsql,mbstring,xml,curl,gd,zip,bcmath}` |
| **Composer** | 2.x | included with Herd, or [getcomposer.org](https://getcomposer.org/download/) | `brew install composer` | [getcomposer.org](https://getcomposer.org/download/) |
| **Node.js** | 20 or newer | [nodejs.org](https://nodejs.org) | `brew install node` | [nodejs.org](https://nodejs.org/en/download/package-manager) |
| **Git** | any | [git-scm.com](https://git-scm.com) | included with Xcode tools | `sudo apt install git` |

**Required PHP extensions:** `pdo_sqlite` (or `pdo_mysql` / `pdo_pgsql`), `mbstring`, `openssl`, `fileinfo`, `gd`, `xml`, `curl`, `zip`. Herd and Homebrew include all of them.

To check your setup:

```bash
php -v          # PHP 8.3+
composer -V     # Composer 2.x
node -v         # v20+
php -m | grep -i pdo     # Windows: php -m | findstr pdo
```

### Step 2: Download the project

```bash
git clone https://github.com/mohakamran/gymflow.git
cd gymflow
```

### Step 3: Choose your database

| Database | Best for | What you need to do |
|---|---|---|
| **SQLite** (default) | Trying it out and local development | Nothing; go straight to Step 4 |
| **MySQL / MariaDB** | Production and shared servers | Follow [MySQL setup](#mysql) first, then come back to Step 4 |
| **PostgreSQL** | Production | Follow [PostgreSQL setup](#postgresql) first, then come back to Step 4 |

### Step 4: Install & set up (one command)

```bash
composer setup
```

This one command:

1. Installs the PHP packages.
2. Creates `.env` from `.env.example` if it doesn't exist yet.
3. Generates the app key.
4. Creates the SQLite database file (SQLite only).
5. Creates all the tables and loads the **demo gym**.
6. Links the storage folder for uploads.
7. Installs the frontend packages and builds the CSS and JavaScript.

<details>
<summary><b>Prefer to run each step by hand?</b> Click here.</summary>

```bash
composer install
npm install
cp .env.example .env                 # Windows (cmd): copy .env.example .env
php artisan key:generate
touch database/database.sqlite       # SQLite only. Windows (cmd): type nul > database\database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run build
```
</details>

### Step 5: Start the app

```bash
composer run dev
```

This starts the **web server, queue worker (emails and notifications), log viewer and Vite** together.

Open **http://localhost:8000** → **Sign in** → copy one of the [demo accounts](#-demo-accounts). 🎉

> If you'd rather start the server on its own, run `php artisan serve` in one terminal and `php artisan queue:work` in a second one.

### Reset the demo data at any time

```bash
php artisan migrate:fresh --seed
```

---

## 🗄️ Database setup

GymFlow is tested on **all three** of these databases. The full test suite of 85 tests passes on each:

| Database | Tested version | Status |
|---|---|---|
| SQLite | 3.x (bundled with PHP) | ✅ Tested, the default |
| MySQL | 8.x and newer (tested on 26.7) | ✅ Tested, recommended for production |
| PostgreSQL | 13 and newer (tested on 18.4) | ✅ Tested |
| MariaDB | 10.6 and newer | MySQL-compatible (`DB_CONNECTION=mysql`) |

### SQLite

There's nothing to set up: the default `.env` already uses SQLite, and the database is a single file at `database/database.sqlite`. `composer setup` creates it for you.

<a id="mysql"></a>
### MySQL (or MariaDB)

**1. Install and start MySQL** (skip if you already have it):

| OS | Install |
|---|---|
| Windows | [MySQL Installer](https://dev.mysql.com/downloads/installer/), [Laragon](https://laragon.org) or [XAMPP](https://www.apachefriends.org) |
| macOS | `brew install mysql && brew services start mysql`, or [DBngin](https://dbngin.com) |
| Linux | `sudo apt install mysql-server && sudo systemctl start mysql` |

**2. Create a database and a user.** Open a MySQL prompt with `mysql -u root -p` and run:

```sql
CREATE DATABASE gymflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gymflow'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT ALL PRIVILEGES ON gymflow.* TO 'gymflow'@'localhost';
FLUSH PRIVILEGES;
```

> Prefer a visual tool? In [phpMyAdmin](https://www.phpmyadmin.net), [MySQL Workbench](https://www.mysql.com/products/workbench/), [TablePlus](https://tableplus.com) or [HeidiSQL](https://www.heidisql.com), create a database named `gymflow` with collation `utf8mb4_unicode_ci`, and a user with full rights on it.

**3. Create `.env` and point it at MySQL:**

```bash
cp .env.example .env               # Windows (cmd): copy .env.example .env
```

Open `.env`, comment out `DB_CONNECTION=sqlite`, and fill in the MySQL lines:

```dotenv
# DB_CONNECTION=sqlite
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gymflow
DB_USERNAME=gymflow
DB_PASSWORD=choose-a-strong-password
```

**4. Run the setup:** `composer setup`. If you've already run it with SQLite, run `php artisan config:clear && php artisan migrate --seed` instead.

<a id="postgresql"></a>
### PostgreSQL

**1. Install and start PostgreSQL:**

| OS | Install |
|---|---|
| Windows | [PostgreSQL installer](https://www.postgresql.org/download/windows/) (includes pgAdmin) |
| macOS | `brew install postgresql@17 && brew services start postgresql@17`, [Postgres.app](https://postgresapp.com) or [DBngin](https://dbngin.com) |
| Linux | `sudo apt install postgresql && sudo systemctl start postgresql` |

**2. Create a database and a user.** Open a prompt with `psql -U postgres` (on Linux: `sudo -u postgres psql`) and run:

```sql
CREATE USER gymflow WITH PASSWORD 'choose-a-strong-password';
CREATE DATABASE gymflow OWNER gymflow ENCODING 'UTF8';
```

> Prefer a visual tool? Use [pgAdmin](https://www.pgadmin.org) or [TablePlus](https://tableplus.com).

**3. Create `.env` and point it at PostgreSQL:** copy `.env.example` to `.env`, comment out `DB_CONNECTION=sqlite`, and set:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gymflow
DB_USERNAME=gymflow
DB_PASSWORD=choose-a-strong-password
```

Make sure the `pdo_pgsql` PHP extension is enabled (check with `php -m | grep pgsql`).

**4. Run the setup:** `composer setup`

### Switching databases later

Change the `DB_*` lines in `.env`, then run:

```bash
php artisan config:clear
php artisan migrate --seed
```

This builds a fresh schema in the new database. Existing data is **not** copied across automatically.

---

## 🔑 Demo accounts

The seeder creates a demo gym, **Iron Peak Fitness**, with about a year of realistic data:

- 120 members, with memberships, invoices and payments
- 3 months of check-ins
- A class timetable with bookings
- Equipment and expenses
- Announcements and leads

It also creates a second gym, **Summit Strength Club**, so you can see that data stays isolated.

All demo accounts use the password **`password`**.

| Role | Email | What you'll see |
|---|---|---|
| 🛡️ Super Admin | `admin@gymflow.test` | Platform console: all gyms, users, SaaS plans, activity |
| 👑 Gym Owner | `owner@gymflow.test` | Everything in Iron Peak Fitness |
| 🧾 Staff / Reception | `staff@gymflow.test` | Check-in, members, memberships, payments, invoices |
| 💪 Trainer | `trainer@gymflow.test` | Assigned members, workout plans, progress, classes |
| 🙋 Member | `member@gymflow.test` | Member portal |
| 👑 Owner of 2nd gym | `owner2@gymflow.test` | A different gym, used to show data isolation |

On the sign-in page, every demo email and the password has a **📋 copy button**. You can also click **Use** to fill in the form for you.

<img src="docs/screenshots/login.png" alt="Login page with copyable demo accounts" width="720">

> The demo panel appears only when `APP_ENV=local`. Set `SHOW_DEMO_ACCOUNTS=true` to show it on a public demo server. Demo accounts are **never** created when `APP_ENV=production`.

---

## 📸 Screenshots

<table>
<tr>
<td width="50%"><b>Owner dashboard</b><br><img src="docs/screenshots/dashboard.png" alt="Dashboard"></td>
<td width="50%"><b>Dark mode</b><br><img src="docs/screenshots/dashboard-dark.png" alt="Dark mode"></td>
</tr>
<tr>
<td><b>Members</b><br><img src="docs/screenshots/members.png" alt="Members list"></td>
<td><b>Member profile</b><br><img src="docs/screenshots/member-profile.png" alt="Member profile"></td>
</tr>
<tr>
<td><b>Front desk check-in</b><br><img src="docs/screenshots/front-desk-check-in.png" alt="Front desk"></td>
<td><b>Self check-in kiosk</b><br><img src="docs/screenshots/kiosk.png" alt="Kiosk"></td>
</tr>
<tr>
<td><b>Sell a membership</b><br><img src="docs/screenshots/sell-membership.png" alt="Sell membership"></td>
<td><b>Membership plans</b><br><img src="docs/screenshots/plans.png" alt="Plans"></td>
</tr>
<tr>
<td><b>Branded invoice (PDF / print / email)</b><br><img src="docs/screenshots/invoice.png" alt="Invoice"></td>
<td><b>Payments</b><br><img src="docs/screenshots/payments.png" alt="Payments"></td>
</tr>
<tr>
<td><b>Class schedule</b><br><img src="docs/screenshots/class-schedule.png" alt="Class schedule"></td>
<td><b>Class roster</b><br><img src="docs/screenshots/class-session.png" alt="Class session"></td>
</tr>
<tr>
<td><b>Profit & loss report</b><br><img src="docs/screenshots/report-profit.png" alt="Profit report"></td>
<td><b>Expenses</b><br><img src="docs/screenshots/expenses.png" alt="Expenses"></td>
</tr>
<tr>
<td><b>Equipment</b><br><img src="docs/screenshots/equipment.png" alt="Equipment"></td>
<td><b>Team</b><br><img src="docs/screenshots/team.png" alt="Team"></td>
</tr>
<tr>
<td><b>Trainer dashboard</b><br><img src="docs/screenshots/trainer-dashboard.png" alt="Trainer dashboard"></td>
<td><b>Branding settings (live preview)</b><br><img src="docs/screenshots/settings-branding.png" alt="Branding"></td>
</tr>
<tr>
<td><b>Member portal</b><br><img src="docs/screenshots/portal-dashboard.png" alt="Member portal"></td>
<td><b>Member class booking</b><br><img src="docs/screenshots/portal-classes.png" alt="Portal classes"></td>
</tr>
<tr>
<td><b>Super admin console</b><br><img src="docs/screenshots/admin-dashboard.png" alt="Admin dashboard"></td>
<td><b>Audit log</b><br><img src="docs/screenshots/audit-log.png" alt="Audit log"></td>
</tr>
<tr>
<td><b>Public gym page (SEO)</b><br><img src="docs/screenshots/public-gym-page.png" alt="Public gym page"></td>
<td><b>Mobile</b><br><img src="docs/screenshots/mobile-dashboard.png" alt="Mobile" width="260"> <img src="docs/screenshots/mobile-menu.png" alt="Mobile menu" width="260"></td>
</tr>
</table>

---

## ✨ Features

### 🏢 Multi-tenant SaaS platform
- **Self-service gym signup.** A new gym gets a 14-day trial, its own workspace and a setup checklist.
- **Complete data isolation between gyms.** Every query is limited to the signed-in user's gym automatically, and record IDs from another gym return *404*.
- **Per-gym branding.** Each gym has its own logo, favicon and brand color, and the whole interface follows them.
- **Per-gym settings.** Currency, timezone, date format, tax, invoice numbering, business hours and notifications.
- **SaaS plans.** Starter, Professional, Business and Enterprise set member and staff limits, which the app enforces. Gyms request plan changes, and super admins approve them.

### 👥 Members
- Full profile: photo, contact details, date of birth, gender, address, emergency contact and staff notes.
- Search, filters (membership state, plan, trainer, status), sorting and pagination.
- Tabs for memberships, billing, attendance, workouts, progress, notes and private documents.
- **QR member card**, printable, used for check-in.
- **Portal invitation.** A single click emails the member a link to set their password.

### 💳 Membership plans & memberships
- Plans of any length (days, weeks, months, years), with price, joining fee, promotional discount, tax, features, weekly class limits and access restrictions.
- **Selling a membership is one step:** it creates the membership and its invoice, and can record payment right away.
- **Renewals** start the day after the current period ends.
- **Freeze and resume:** the end date is extended by the number of frozen days. Memberships can also be cancelled.
- **Status is tracked automatically:** Active, Expiring, Expired, Frozen and Upcoming.

### ✅ Attendance
- Front-desk check-in and check-out by member search, member code or **QR scan**.
- **Kiosk mode:** a full-screen self check-in page that works with USB or Bluetooth QR scanners.
- Live "in the gym now" count, plus history with daily charts and filters.
- Optional rule: members need an active membership to check in.

### 🧑‍🏫 Staff & trainers
- Invite owners, staff and trainers by email, each with a role, job title, specialization, bio and weekly schedule.
- Team accounts can be deactivated or reactivated. The gym always keeps at least one owner.
- **Trainers** see only the members assigned to them, and can write workout plans, record measurements (with a weight chart) and add notes.

### 📅 Classes & schedule
- Class types (Yoga, HIIT, Spin…) with a color, default trainer, capacity, duration and location.
- **Weekly timetable** with one-off or **recurring** sessions.
- Bookings respect capacity and the **weekly class limits** of the member's plan.
- Staff book members in and mark them attended or no-show. Members can book online.
- A single session or the whole series can be cancelled, and any bookings are released.

### 🏋️ Equipment
- Inventory with category, quantity, cost, serial number, location, condition and status (Active, Under maintenance, Damaged, Retired).
- Maintenance log, next-service dates, overdue warnings and reminders.

### 🧾 Payments, invoices & expenses
- **Invoices** with line items, discounts, tax and **per-gym sequential numbers**. They can be viewed, printed, downloaded as **PDF**, or **emailed** with the PDF attached, and voided.
- **Payments**: cash, card, bank transfer, mobile wallet or online.
  - Partial payments and full or partial **refunds**.
  - The invoice status always stays in sync with its payments.
- **Payment gateway architecture**, ready for Stripe, PayPal or local gateways.
- **Expenses**: rent, utilities, salaries, maintenance, marketing and more, with receipt uploads and profit summaries.

### 📊 Dashboards & reports
- **Dashboard KPIs:**
  - Members, active and expiring memberships, and today's attendance.
  - Revenue compared with last month, and pending payments.
  - Trainers and equipment status.
- **Dashboard charts:** revenue vs expenses, attendance trend, membership growth, membership types and expenses by category. Every chart can also be viewed as a table.
- **11 reports:** Revenue, Expenses, Profit & Loss, Payments, Membership sales, Expired memberships (a win-back list), Member growth, Attendance, Trainers, Classes and Equipment.
- **Filters:** date-range presets, plan, trainer, payment method, status and category.
- **Exports:** CSV/Excel, PDF and print.

### 🔔 Notifications
- **Email and in-app** notifications, with **SMS and WhatsApp channels** ready to connect.
- Each gym can switch these on or off:
  - Membership expiry reminders and confirmations.
  - Payment receipts and payment-due reminders.
  - Class reminders.
  - Announcements.
  - Equipment maintenance alerts.
- **Announcements** for members and staff can be pinned and optionally emailed.

### 🙋 Member portal
- Membership status, a **QR check-in pass**, upcoming classes and announcements.
- Book and cancel classes, view workout plans and progress charts.
- Invoices (with PDF download), payments, attendance history and notifications.

### 🌐 Public gym page & leads
- Each gym can enable an SEO-friendly page at `/g/{slug}` showing its plans, the week's classes, coaches, opening hours, map link and contact details.
- **Search engine support:** meta tags, OpenGraph, schema.org `ExerciseGym` data and an XML sitemap.
- **Lead capture form** with spam protection. Leads land in the gym's **Leads** inbox.

### 🔒 Security
- Hashed passwords, email verification, password reset, and rate limiting on login, registration, password reset and uploads.
- Role and permission checks in middleware, **policies** and form requests.
- CSRF protection, escaped output, parameterised queries and security headers.
- Safe uploads: images only (no SVG), and member documents and receipts on **private** storage.
- Exported CSV files are protected against spreadsheet formula injection.
- **Audit log** of logins, failed logins and changes to members, payments, invoices, memberships, settings and users, with before and after values.

### 🎨 Interface
- Responsive sidebar, mobile navigation, **dark and light mode**, toasts, confirmation dialogs, empty states and loading states.
- Works on desktop, tablet and phone.

---

## 👤 Who can use it: roles & permissions

| Capability | 🛡️ Super Admin | 👑 Owner | 🧾 Staff | 💪 Trainer | 🙋 Member |
|---|:-:|:-:|:-:|:-:|:-:|
| Platform console (all gyms, users, SaaS plans) | ✅ | | | | |
| Gym dashboard | | ✅ | ✅ | ✅ (own view) | |
| Members | | ✅ | ✅ | assigned only | |
| Sell / renew / freeze memberships | | ✅ | ✅ | | |
| Check-in & kiosk | | ✅ | ✅ | | |
| Payments & invoices | | ✅ | ✅ | | own (portal) |
| Class schedule | | ✅ manage | ✅ view & book | ✅ manage | ✅ book (portal) |
| Workout plans, progress, notes | | ✅ | | assigned members | own (portal) |
| Announcements | | ✅ | ✅ | | read |
| Team, plans, equipment, expenses, reports | | ✅ | | | |
| Gym settings, branding, SaaS billing | | ✅ | | | |
| Audit log | ✅ (all gyms) | ✅ | | | |

Roles are defined in [`app/Enums/Role.php`](app/Enums/Role.php) and permissions in [`app/Enums/Permission.php`](app/Enums/Permission.php). To sync them after changing either file:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## 🧰 Technologies used

### Backend

| Technology | Version | Used for |
|---|---|---|
| [PHP](https://www.php.net) | 8.3+ | Language |
| [Laravel](https://laravel.com) | 13 | Web framework: routing, ORM, queues, scheduler, mail, validation |
| [Eloquent ORM](https://laravel.com/docs/eloquent) | — | Database models, relationships and gym-level scoping |
| [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) | 8.3 | Roles & permissions |
| [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) | 3.1 | PDF invoices and reports |
| Laravel Notifications & Queues | — | Emails, in-app notifications, SMS/WhatsApp channels |

### Frontend

| Technology | Version | Used for |
|---|---|---|
| [Blade](https://laravel.com/docs/blade) | — | Server-rendered pages and a reusable component library |
| [Tailwind CSS](https://tailwindcss.com) | 4.3 | Styling, dark mode and per-gym brand colors |
| [Alpine.js](https://alpinejs.dev) | 3.17 | Interactivity: menus, dialogs, toasts, live previews, copy buttons |
| [Chart.js](https://www.chartjs.org) | 4.5 | Dashboard and report charts |
| [qrcode](https://github.com/soldair/node-qrcode) | 1.5 | Member QR codes |
| [Heroicons](https://heroicons.com) | — | Icons |
| [Vite](https://vitejs.dev) | 8 | Frontend build tool |

### Databases

| Database | Used for |
|---|---|
| [SQLite](https://www.sqlite.org) | Default for local development, and for the test suite |
| [MySQL](https://www.mysql.com) / [MariaDB](https://mariadb.org) | Production |
| [PostgreSQL](https://www.postgresql.org) | Production |

### Development & quality

| Tool | Used for |
|---|---|
| [PHPUnit](https://phpunit.de) 12 | 85 automated tests |
| [Laravel Pint](https://laravel.com/docs/pint) | Code style |
| [Composer](https://getcomposer.org) / [npm](https://www.npmjs.com) | Package managers |
| [Git](https://git-scm.com) / [GitHub](https://github.com) | Version control |

---

## ⚙️ Configuration

All settings live in `.env`; [`.env.example`](.env.example) lists them with comments.

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | `GymFlow` | Product name |
| `APP_ENV` | `local` | `production` enforces strong passwords and skips demo data |
| `APP_DEBUG` | `true` | **Must be `false` in production** |
| `APP_URL` | `http://localhost:8000` | Used in emails and links |
| `DB_CONNECTION` | `sqlite` | `sqlite`, `mysql` or `pgsql` ([details](#-database-setup)) |
| `QUEUE_CONNECTION` | `database` | Background jobs (use `redis` at scale) |
| `MAIL_MAILER` | `log` | Locally, emails are written to `storage/logs/laravel.log`. Set SMTP for real emails |
| `SHOW_DEMO_ACCOUNTS` | on when `local` | Show the copyable demo accounts on the login page |
| `TEXT_MESSAGING_DRIVER` | `log` | SMS/WhatsApp provider ([extending](#-extending-gymflow)) |
| `FILESYSTEM_DISK` | `local` | Use S3 by filling in the `AWS_*` variables |

App-level options (currencies, upload limits, payment gateways, demo accounts) are in [`config/gym.php`](config/gym.php).

---

## ⏰ Background jobs & scheduler

Emails and notifications are **queued**. `composer run dev` starts a worker automatically. In production, keep one running:

```bash
php artisan queue:work --tries=3
```

Reminders run from Laravel's scheduler. In production, add **one** cron entry:

```cron
* * * * * cd /path/to/gymflow && php artisan schedule:run >> /dev/null 2>&1
```

| Command | Runs | Purpose |
|---|---|---|
| `gym:memberships:refresh` | hourly | Activate memberships that have started; expire memberships that have ended |
| `gym:reminders:expiring` | daily 09:00 | Email members before their membership ends |
| `gym:reminders:payment-due` | daily 10:00 | Remind members about overdue invoices, at most once a week |
| `gym:reminders:classes` | every 15 minutes | Remind booked members about upcoming classes |
| `gym:reminders:maintenance` | daily 08:00 | Alert owners about equipment that is due for service |

You can run any command by hand, for one gym or all of them: `php artisan gym:reminders:expiring --tenant=1`.

---

## 🧪 Testing

```bash
php artisan test
```

**85 tests (600+ assertions)** run against an in-memory SQLite database, so your data is never touched. The same suite also passes on MySQL and PostgreSQL. To test against another database, set the `DB_*` environment variables before running `php artisan test`. They cover:

- **Data isolation:** another gym's IDs in URLs, lookups, reports, portal invoices and attendance codes.
- **Authentication:** registration, email verification, login rate limiting and password reset.
- **Roles:** the access matrix for every role, and trainers seeing only assigned members.
- **Billing:** tax, joining fees, sequential invoice numbers, partial payments, refunds and voiding.
- **Memberships:** renewals, freeze and resume, and scheduled expiry.
- **Operations:** QR check-in, class capacity and weekly limits, reminder commands and notification preferences.
- **A full smoke test** that seeds the demo gym and renders **every page as every role**, along with all CSV, PDF and print exports.

Code style uses [Laravel Pint](https://laravel.com/docs/pint): `vendor/bin/pint`.

---

## 🏗️ Architecture

**Single database, scoped per gym.** Every gym-owned table has a `tenant_id` column:

| Piece | Responsibility |
|---|---|
| [`TenantContext`](app/Support/Tenancy/TenantContext.php) | Holds the current gym for the request or job |
| [`IdentifyTenant`](app/Http/Middleware/IdentifyTenant.php) middleware | Sets the gym from the signed-in user. It runs **before** route model binding, so other gyms' IDs return 404 |
| [`BelongsToTenant`](app/Models/Concerns/BelongsToTenant.php) trait | Filters every query to the current gym, sets `tenant_id` on new records, and blocks moving a record to another gym |
| [Policies](app/Policies) | Check the gym and the permission again, as a second line of defence |

**Business logic lives in services**, such as [`MembershipService`](app/Services/MembershipService.php), [`InvoiceService`](app/Services/InvoiceService.php), [`PaymentService`](app/Services/PaymentService.php), [`AttendanceService`](app/Services/AttendanceService.php), [`ClassScheduleService`](app/Services/ClassScheduleService.php) and [`ReportService`](app/Services/Reporting/ReportService.php). Controllers stay thin.

**Dates and money:**
- Timestamps are stored in UTC and shown in each gym's timezone.
- Calendar dates are stored as plain `Y-m-d` values, using the [`DateOnly`](app/Casts/DateOnly.php) cast, so they compare the same way on every database.
- The helpers `money()`, `format_date()`, `tenant_today()` and `tenant_now()` are in [`app/Support/helpers.php`](app/Support/helpers.php).

---

## 📁 Project structure

```
app/
├── Casts/                 DateOnly (portable calendar dates)
├── Console/Commands/      Scheduled reminders (run once per gym)
├── Enums/                 Roles, permissions, statuses, categories
├── Http/
│   ├── Controllers/       Admin, Auth, Billing, Classes, Members, Operations, Portal, Settings
│   ├── Middleware/        IdentifyTenant, EnsureTenantUser, SecurityHeaders
│   └── Requests/          Validation + authorization
├── Models/                Eloquent models (gym-owned ones use BelongsToTenant)
├── Notifications/         Gym-branded notifications + SMS/WhatsApp channels
├── Payments/              Payment gateway contract + manual gateway
├── Policies/              Per-model authorization
├── Services/              Business logic + Reporting/
└── Support/               TenantContext, sidebar Navigation, helpers
config/gym.php             Currencies, uploads, gateways, messaging, demo accounts
database/                  Migrations, factories, seeders (incl. demo gym)
resources/
├── css/app.css            Tailwind theme (gym brand color via CSS variable)
├── js/                    Alpine components, Chart.js setup, QR codes, clipboard
└── views/                 Blade pages + components/ (design system)
routes/web.php             All routes, grouped by area and permission
routes/console.php         Scheduler
tests/                    85 feature & unit tests
docs/screenshots/          README images
```

---

## 🌍 Deploying to production

1. **Server:** PHP 8.3+, MySQL 8+ or PostgreSQL 13+, Nginx or Apache with the document root set to `public/`, and HTTPS.
2. **Database:** create the database and user ([MySQL](#mysql) · [PostgreSQL](#postgresql)).
3. **Install dependencies and build the frontend:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
4. **Configure `.env`:**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain`
   - Database `DB_*` settings
   - SMTP `MAIL_*` settings
   - `SESSION_SECURE_COOKIE=true`
5. **Set up the database and cache:**
   ```bash
   php artisan key:generate          # first deploy only
   php artisan migrate --force
   php artisan db:seed --class=RolesAndPermissionsSeeder --force
   php artisan storage:link
   php artisan optimize
   ```
6. **Background work:** run `php artisan queue:work` under Supervisor or systemd, and add the scheduler cron entry ([see above](#-background-jobs--scheduler)).
7. **Create your super admin:**
   ```bash
   php artisan tinker
   >>> $u = App\Models\User::create(['name' => 'Admin', 'email' => 'you@company.com', 'password' => 'a-long-password']);
   >>> $u->forceFill(['email_verified_at' => now()])->save(); $u->assignRole('super_admin');
   ```

Make sure `storage/` and `bootstrap/cache/` are writable by the web server.

---

## 🧩 Extending GymFlow

| To add… | Do this |
|---|---|
| **Stripe / PayPal / local gateway** | Implement [`PaymentGateway`](app/Payments/PaymentGateway.php) (`charge()`, `refund()`) and register it in `config/gym.php` → `payment_gateways` |
| **SMS / WhatsApp provider** | Implement [`TextMessenger`](app/Notifications/Messaging/TextMessenger.php), register it under `text_messaging.drivers`, then set `TEXT_MESSAGING_DRIVER` |
| **A new notification** | Extend [`GymNotification`](app/Notifications/GymNotification.php). Queueing, the gym's context, its on/off setting and SMS delivery are handled for you |
| **A new module** | Add routes. The sidebar shows the module automatically ([`Navigation`](app/Support/Navigation.php)) for users with the permission. Give gym-owned models `use BelongsToTenant` |

---

## 🛠️ Troubleshooting

| Problem | Fix |
|---|---|
| `Vite manifest not found` | Run `npm run build` (or keep `npm run dev` running) |
| Images or logos don't show | Run `php artisan storage:link` |
| Emails or notifications never arrive | Start a queue worker: `php artisan queue:work`. Locally, emails are in `storage/logs/laravel.log` |
| `could not find driver` | Enable the `pdo_sqlite`, `pdo_mysql` or `pdo_pgsql` PHP extension in `php.ini` |
| `SQLSTATE[HY000] [2002] Connection refused` | Make sure MySQL/PostgreSQL is running, and check `DB_HOST` and `DB_PORT` |
| `Access denied for user` | Check `DB_USERNAME` and `DB_PASSWORD`, and that the user has rights on the database |
| `database.sqlite does not exist` | `touch database/database.sqlite`, then `php artisan migrate --seed` |
| `No application encryption key` | `php artisan key:generate` |
| Settings changes don't apply | `php artisan optimize:clear` |
| Locked out after failed logins | Wait 60 seconds (login rate limit) |

---

## 📄 License

Copyright © 2026. All rights reserved. See [CHANGELOG.md](CHANGELOG.md) for release history.

If you find a security issue, please report it privately instead of opening a public issue. Never commit `.env` files or real credentials.
