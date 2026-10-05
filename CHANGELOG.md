# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added: Database support
- Verified on **SQLite, MySQL and PostgreSQL**: migrations, demo seeding and all 85 tests pass on each.
- `composer setup` installs and sets up everything in one command, for any database.
- Step-by-step MySQL and PostgreSQL setup instructions for Windows, macOS and Linux.

### Fixed
- MySQL `ONLY_FULL_GROUP_BY` error on the Team page.
- Searches are now case-insensitive on every database (PostgreSQL `LIKE` is case-sensitive).

### Added: Developer experience
- Demo accounts panel on the login page with one-click copy buttons for every email and the password, plus a **Use** button that fills in the form. Controlled by `SHOW_DEMO_ACCOUNTS` (on by default when `APP_ENV=local`).
- README with screenshots of every area, a 5-minute quick start, a role matrix and troubleshooting.

### Changed
- More realistic demo data (120 members; costs scaled to a small gym).
- Wider front-desk visits table; badges and times no longer wrap; cards can't overflow their grid column.
- Invoice line-item columns no longer run together.

### Added: Phase 6, portal and platform
- Member portal: membership, QR check-in pass, class booking and cancellation, workouts, progress, invoices (PDF), payments, attendance, announcements, notifications.
- Public SEO gym page (`/g/{slug}`) with schema.org data, a lead-capture form (with honeypot) and an XML sitemap. Leads inbox for staff.
- SaaS plan limits (members and staff) enforced; plan change requests from gyms with super-admin approval.
- Super admin: all users (enable/disable), SaaS plans and requests, platform activity log.

### Added: Phase 5, insight and communication
- Dashboard KPIs and charts (Chart.js, validated colorblind-safe palette, each chart also viewable as a table).
- Report engine with 11 reports, date presets and filters, plus CSV/Excel (formula-injection safe), PDF and print exports.
- Queued, gym-branded notifications (email and in-app, SMS/WhatsApp channel abstraction), each switchable per gym.
- Scheduled commands: membership status refresh, plus reminders for expiry, payment due, classes and maintenance.
- Announcements with optional email broadcast.

### Added: Phase 4, classes and equipment
- Class types, weekly timetable, one-off and recurring sessions, capacity and weekly class limits, bookings, attended/no-show tracking, session and series cancellation.
- Equipment inventory with status, condition, value, maintenance log and due dates.

### Added: Phase 3, billing
- Invoices with line items, discounts, tax, per-gym sequential numbering, PDF, print, email with attachment, and void.
- Payments with methods, partial payments, refunds and invoice status sync; pluggable payment gateway architecture.
- Expenses with categories, receipts, and revenue/profit summaries.

### Added: Phase 2, members and operations
- Members CRUD with search, filters, photo, documents (private storage), notes, QR member card, and portal invitations.
- Membership plans and memberships: sell, renew, freeze/resume, cancel, automatic status tracking.
- Front-desk check-in/out by search, QR or code; kiosk mode; attendance history.
- Team management with invitations, roles, profiles, schedules and last-owner protection.
- Trainer workflows: assigned members, workout plans, progress measurements.

### Added: Phase 1, foundation
- Multi-tenant architecture (shared database, automatic `tenant_id` scoping and stamping).
- Gym registration, authentication, email verification, password reset, rate limiting.
- Roles and permissions, audit log, gym settings and branding, super admin console, marketing page.

### Fixed
- Route model binding now runs after tenant scoping, so record IDs from another gym return 404.
- Calendar dates are stored as `Y-m-d` on every database driver (new `DateOnly` cast).
