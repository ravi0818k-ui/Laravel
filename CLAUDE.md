# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PG A1 is a full-stack PG (Paying Guest) accommodation management system for properties in Gurugram, India. It has a Laravel 12 (PHP 8.2+) REST API backend and static HTML/vanilla-JS dashboards as the frontend (no SPA framework, no build step required for the dashboards themselves).

Repo root contains both the marketing site (`index.html`, `onboarding.html`, `images/`) and the actual application in `backend/`. Treat `backend/` as the working directory for almost everything.

## Commands

All commands run from `backend/`.

```bash
# Install PHP deps
composer install

# First-time setup
cp .env.example .env
php artisan key:generate
php artisan migrate

# Run the app (API + dashboards both served by Laravel)
php artisan serve
# or, when testing large file uploads (payment screenshots, meter images, documents):
php -c php-server.ini artisan serve

# All-in-one dev (server + queue listener + logs + vite), if using the Vite/Tailwind pipeline:
composer run dev

# Cache clearing (needed often when routes/config act stale)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

App runs at `http://127.0.0.1:8000`. Dashboards: `/dashboard/login.html`, `/dashboard/admin.html`, `/dashboard/super-admin.html`, `/dashboard/tenant.html`. Default super admin: mobile `9999999999` / password `admin123`.

### Testing

There are two, unrelated test setups — don't confuse them:

1. **PHPUnit** (`tests/Feature`, `tests/Unit`) — standard Laravel tests, currently just the framework's example tests.
   ```bash
   php artisan test
   php artisan test --filter=testName
   php artisan test tests/Feature/ExampleTest.php
   ```
2. **`tests/flow_test.php`** — a standalone, hand-rolled integration test script (not PHPUnit) that boots the full app and asserts against **real data already in the database** (not factories/seeders). It checks models, relationships, services, middleware registration, and data-integrity invariants end-to-end. Run it directly with PHP after the app has real seed data:
   ```bash
   php tests/flow_test.php
   ```
   Because it depends on existing DB rows (e.g. "an occupied bed must have a current allocation," "at least one PG location exists"), only run/trust it against a populated dev/staging database, not a fresh empty one.

There's an accidentally-nested `tests/tests/` directory duplicating `Feature`/`Unit` — a stray artifact, not a real second suite. Don't add tests there.

## Architecture

### Roles & authorization model

Three roles, enforced via the `role:` middleware alias (`App\Http\Middleware\EnsureRole`, registered in `bootstrap/app.php`) applied per route group in `routes/api.php`:

- **super_admin** — manages PG locations, creates/deactivates admin accounts, assigns admins to PG locations.
- **admin** — scoped to PG location(s) assigned via `admin_pg_assignments`; manages tenants, rooms/beds, payments, onboarding, electricity, expenses, notes for those PGs only.
- **tenant** — self-service: own dashboard, rent, payment submission, electricity view, profile.

A second middleware, `EnsureAdminPgAccess` (alias `pg.access`), enforces per-PG-location scoping for admins on top of the role check — it reads `pg_location_id` from the route or request body and checks `user->assignedPgLocations()`, and is bypassed entirely for super admins. When adding admin-scoped endpoints that touch a specific PG, wire this middleware in rather than re-deriving PG access manually inside the controller.

Auth is Laravel Sanctum, token-based (`Authorization: Bearer <token>`), single `POST /api/v1/login` endpoint for all roles — the role comes off the authenticated `User` model, not separate login routes.

### Request flow

`routes/api.php` is a single flat file, all routes under `/api/v1`, grouped by public / `auth:sanctum` / role. Controllers live in `app/Http/Controllers/Api/` (one per resource: `AdminTenantController`, `BedController`, `ElectricityController`, `RentController`, `OnboardingController`, `PaymentController`, `PgController`, `RoomController`, `SuperAdminController`, `TenantController`, `AuthController`, `NoteController`, `ExpenseController`). Controllers are thin; non-trivial business logic is pulled out into `app/Services/`:

- `RentService` — generates `MonthlyRent` rows per active tenant per billing month (idempotent via `firstOrCreate` on `tenant_id` + `billing_month`).
- `RoomAllocationService` — bed allocation/vacate logic wrapped in `DB::transaction`; vacating a bed flips it back to `available` and closes out the prior `TenantBedAllocation`.
- `ElectricityBillingService` — room-wise electricity bill creation and even splitting across active tenants in that room (`per_tenant_amount = total_amount / active_tenants_count`), producing `ElectricityBillAllocation` rows.
- `TenantIdService` — generates sequential, PG-scoped tenant IDs (e.g. `TSN0001`) using `lockForUpdate()` on the PG location's counter row to avoid race conditions under concurrent onboarding approvals.
- `OffboardingService`, `ActivityLogService` — offboarding workflow and audit-log writes respectively.

Follow this pattern for new business logic: keep it in a service, keep multi-step writes inside `DB::transaction`, keep controllers as request validation + service call + response shaping.

### Domain model shape

Core chain: `PgLocation` → `Room` → `Bed` → `TenantBedAllocation` → `Tenant` (→ `User` for auth). A `Tenant` has at most one *current* bed allocation (`is_current = true`); history is preserved by inserting a new allocation row and flipping the old one rather than mutating it.

Money flow: `MonthlyRent` (per tenant per month) accumulates `base_rent` + `additional_charge` (e.g. merged-in electricity) − `discount` = `total_amount`; `PaymentSubmission` records tenant-submitted proof (screenshot) or cash, verified by an admin (`verify`/`reject` endpoints), which updates `paid_amount`/`due_amount`/`status` on the `MonthlyRent`. Some `PaymentSubmission` rows have a null `monthly_rent_id` (e.g. first payment covering rent + security deposit before a rent record exists) — this is expected, not a data bug.

Electricity: `ElectricityBill` (per room per month, with meter images) splits into one `ElectricityBillAllocation` per active tenant in that room; allocations can later be merged into a tenant's `MonthlyRent.additional_charge`.

Onboarding: `OnboardingInvitation` has a `link_type` of `bulk` / `single` / `existing` (existing-tenant re-verification links serve `verification.html` instead of `onboarding.html`, see `routes/web.php`). Candidate submits via public token-based routes, uploads go to `storage/app/onboarding/{id}/` as `TenantDocument` rows, and admin approval creates the `User` + `Tenant` + bed allocation in one step.

Tenants use soft deletes (trash/restore/force-delete endpoints under `/admin/tenants/...`), so most tenant queries should go through the `active()` scope or be explicit about `withTrashed()`/`onlyTrashed()`.

### Error handling convention

`bootstrap/app.php` has a global `renderable()` handler for `QueryException` that translates known MySQL error codes (1062 duplicate key by constraint name, 1451/1452 FK violations) into user-friendly JSON `422` responses instead of leaking raw SQL errors to the API/frontend. When adding a new unique constraint, add its match here (by constraint name substring) rather than handling it ad hoc in the controller.

### Frontend dashboards

Plain HTML + vanilla JS, served directly by Laravel from `backend/public/dashboard/` (`login.html`, `admin.html`, `super-admin.html`, `tenant.html`). `backend/dashboards/` is a source copy of the same files used when developing the dashboards against a separately-served static server — the two directories are meant to be kept in sync when the built/served version is `public/dashboard/`; treat `public/dashboard/` as the deployed source of truth.

All API calls go through `public/dashboard/js/api.js`, which auto-detects the API base URL:
```javascript
const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
  ? 'http://127.0.0.1:8000/api/v1'
  : '/api/v1';
```
**Never hardcode `http://127.0.0.1:8000` or a production URL in dashboard JS** — always route through `API_BASE`/the shared `api` client, since the same files are deployed to production unmodified.

### Production deployment

Deployed to Serverbyt shared hosting (no SSH/composer access there — `vendor/` must be uploaded, storage symlink created via a one-off PHP script, not `artisan storage:link`). The Laravel app lives outside the web root (`~/pga1/`) with `~/public_html/index.php` pointed at it via `usePublicPath`. See `SERVERBYT-DEPLOYMENT-GUIDE.md` and `serverByteSetup.md` (root directory) for the full, previously-debugged deployment steps — consult these before changing anything related to `public_html`, `.htaccess`, or `index.php` entry points, since several non-obvious fixes are already recorded there.
