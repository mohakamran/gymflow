<div align="center">

# 🏋️ GymFlow

### All-in-one gym management software: one workspace per gym

Members, memberships, check-ins, payments, invoices, classes, trainers, equipment, reports and a member portal, branded with each gym's logo and colors.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white)](https://alpinejs.dev)
[![Tests](https://img.shields.io/badge/tests-76%20passing-22c55e)](#-testing)
[![Database](https://img.shields.io/badge/DB-SQLite%20%7C%20MySQL-4479A1?logo=mysql&logoColor=white)](#-using-mysql)

![GymFlow dashboard](docs/screenshots/dashboard.png)

</div>

---

## 📚 Table of contents

1. [What is GymFlow?](#-what-is-gymflow)
2. [Quick start (5 minutes)](#-quick-start-5-minutes)
3. [Demo accounts](#-demo-accounts)
4. [Screenshots](#-screenshots)
5. [Features](#-features)
6. [Who can use it: roles & permissions](#-who-can-use-it-roles--permissions)
7. [Using MySQL](#-using-mysql)
8. [Configuration](#-configuration)
9. [Background jobs & scheduler](#-background-jobs--scheduler)
10. [Testing](#-testing)
11. [Architecture](#-architecture)
12. [Project structure](#-project-structure)
13. [Deploying to production](#-deploying-to-production)
14. [Extending GymFlow](#-extending-gymflow)
15. [Troubleshooting](#-troubleshooting)
16. [Tools & libraries](#-tools--libraries)
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

## 🚀 Quick start (5 minutes)

### Requirements

| Tool | Version | Get it |
|---|---|---|
| PHP | 8.3 or newer, with `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd` | [php.net](https://www.php.net/downloads) · macOS: `brew install php` · Windows: [Laravel Herd](https://herd.laravel.com) |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org/download/) |
| Node.js | 20 or newer (includes npm) | [nodejs.org](https://nodejs.org) |
| Git | any | [git-scm.com](https://git-scm.com) |

> 💡 **Easiest on Windows and macOS:** [Laravel Herd](https://herd.laravel.com) installs PHP and Composer in one step.

### Install & run

```bash
# 1. Get the code
git clone https://github.com/mohakamran/gymflow.git
cd gymflow

# 2. Install dependencies
composer install
npm install

# 3. Create your environment file and app key
cp .env.example .env              # Windows (cmd): copy .env.example .env
php artisan key:generate

# 4. Create the SQLite database, tables and demo data
touch database/database.sqlite    # Windows (cmd): type nul > database\database.sqlite
php artisan migrate --seed
php artisan storage:link

# 5. Build the frontend
npm run build

# 6. Start everything (web server + queue worker + logs + Vite)
composer run dev
```

Open **http://localhost:8000**, click **Sign in**, and use one of the [demo accounts](#-demo-accounts).

> If you'd rather start the server on its own, run `php artisan serve` in one terminal and `php artisan queue:work` in a second one. The queue worker sends emails and notifications.

**No database server is needed.** By default GymFlow uses SQLite, which is a single file at `database/database.sqlite`. To use MySQL, see [Using MySQL](#-using-mysql).

### Reset the demo data at any time

```bash
php artisan migrate:fresh --seed
```

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

## 🐬 Using MySQL

SQLite is perfect for local development. Use **MySQL 8+** (or MariaDB 10.6+) for production.

**1. Create a database and user** by running this in `mysql -u root -p`:

```sql
CREATE DATABASE gymflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gymflow'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON gymflow.* TO 'gymflow'@'localhost';
FLUSH PRIVILEGES;
```

**2. Point `.env` at MySQL**, replacing the `DB_CONNECTION=sqlite` line:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gymflow
DB_USERNAME=gymflow
DB_PASSWORD=a-strong-password
```

**3. Create the tables:**

```bash
php artisan config:clear
php artisan migrate --seed
```

The same migrations run on both SQLite and MySQL. Switching databases builds a fresh schema; existing data is **not** copied over.

---

## ⚙️ Configuration

All settings live in `.env`; [`.env.example`](.env.example) lists them with comments.

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | `GymFlow` | Product name |
| `APP_ENV` | `local` | `production` enforces strong passwords and skips demo data |
| `APP_DEBUG` | `true` | **Must be `false` in production** |
| `APP_URL` | `http://localhost:8000` | Used in emails and links |
| `DB_CONNECTION` | `sqlite` | `sqlite` or `mysql` ([details](#-using-mysql)) |
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

**76 feature tests (600+ assertions)** run against an in-memory SQLite database, so your data is never touched. They cover:

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
tests/Feature/             76 feature tests
docs/screenshots/          README images
```

---

## 🌍 Deploying to production

1. **Server:** PHP 8.3+, MySQL 8, Nginx or Apache with the document root set to `public/`, and HTTPS.
2. **Install dependencies and build the frontend:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
3. **Configure `.env`:**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain`
   - MySQL `DB_*` settings
   - SMTP `MAIL_*` settings
   - `SESSION_SECURE_COOKIE=true`
4. **Set up the database and cache:**
   ```bash
   php artisan key:generate          # first deploy only
   php artisan migrate --force
   php artisan db:seed --class=RolesAndPermissionsSeeder --force
   php artisan storage:link
   php artisan optimize
   ```
5. **Background work:** run `php artisan queue:work` under Supervisor or systemd, and add the scheduler cron entry ([see above](#-background-jobs--scheduler)).
6. **Create your super admin:**
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
| `could not find driver` | Enable the `pdo_sqlite` or `pdo_mysql` PHP extension |
| `database.sqlite does not exist` | `touch database/database.sqlite`, then `php artisan migrate --seed` |
| `No application encryption key` | `php artisan key:generate` |
| Settings changes don't apply | `php artisan optimize:clear` |
| Locked out after failed logins | Wait 60 seconds (login rate limit) |

---

## 🧰 Tools & libraries

| Purpose | Library |
|---|---|
| Framework | [Laravel 13](https://laravel.com/docs) |
| Templates | [Blade](https://laravel.com/docs/blade) components |
| Styling | [Tailwind CSS 4](https://tailwindcss.com/docs) |
| Interactivity | [Alpine.js](https://alpinejs.dev) |
| Build tool | [Vite](https://vitejs.dev) |
| Roles & permissions | [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) |
| PDF invoices & reports | [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) |
| Charts | [Chart.js](https://www.chartjs.org) |
| QR codes | [qrcode](https://github.com/soldair/node-qrcode) |
| Icons | [Heroicons](https://heroicons.com) |
| Testing | [PHPUnit](https://phpunit.de) |
| Code style | [Laravel Pint](https://laravel.com/docs/pint) |
| Database | [SQLite](https://www.sqlite.org) (dev) · [MySQL](https://www.mysql.com) (production) |

---

## 📄 License

Copyright © 2026. All rights reserved. See [CHANGELOG.md](CHANGELOG.md) for release history.

If you find a security issue, please report it privately instead of opening a public issue. Never commit `.env` files or real credentials.
