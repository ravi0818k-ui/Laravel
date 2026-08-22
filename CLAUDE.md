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

# When testing file uploads (payment screenshots, meter images, onboarding documents) on Windows,
# `php -c php-server.ini artisan serve` looks right but does NOT work — see note below. Use instead:
$env:PHP_INI_SCAN_DIR = "$PWD\php-ini-scan"; php artisan serve --no-reload   # PowerShell
PHP_INI_SCAN_DIR="$(pwd)/php-ini-scan" php artisan serve --no-reload         # bash

# All-in-one dev (server + queue listener + logs + vite), if using the Vite/Tailwind pipeline:
composer run dev

# Cache clearing (needed often when routes/config act stale)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

App runs at `http://127.0.0.1:8000`. Dashboards: `/dashboard/login.html`, `/dashboard/admin.html`, `/dashboard/super-admin.html`, `/dashboard/tenant.html`. Default super admin: mobile `9999999999` / password `admin123`.

### Why `php -c php-server.ini artisan serve` doesn't work (and file uploads fail with it)

`artisan serve` doesn't run the dev server itself — it spawns a **separate** `php -S host:port server.php` child process (`ServeCommand::serverCommand()` in `vendor/laravel/framework`) to actually handle requests. That child is launched via a bare `php_binary()` path with no `-c`/`-d` flags at all, so any ini file passed to the outer `artisan serve` invocation never reaches the process that actually parses uploads — `upload_max_filesize`, `post_max_size`, and any other setting in `php-server.ini` are silently ignored for real requests. Worse, whenever a `.env` file exists and `--no-reload` isn't passed, `ServeCommand` deliberately strips almost every environment variable from that child process (`$passthroughVariables` allowlist) — including `TEMP`/`TMP`. On Windows, `sys_get_temp_dir()` depends on those, so without them PHP falls back to `C:\Windows` (not writable), and **every** file upload fails with "unable to create a temporary file" / `UPLOAD_ERR_NO_TMP_DIR` — a validation-looking 422 that has nothing to do with the request payload.

The fix that actually reaches the worker process: `--no-reload` (stops the env-stripping) plus `PHP_INI_SCAN_DIR` pointing at `backend/php-ini-scan/` (an env var, unlike `-c`, that PHP itself reads on process startup regardless of how it's spawned) — see the commands above. `php-server.ini` still works fine for direct one-off invocations (`php -c php-server.ini some-script.php`) that don't go through `artisan serve`'s child-process spawning; it just never helped the dev server itself. Tradeoff: `--no-reload` also disables `artisan serve`'s normal auto-restart-on-`.env`-change behavior — restart the server manually after editing `.env`.

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

Every submission creates a *new* child `OnboardingInvitation` row (holding the candidate's data) linked back to the original link via `parent_invitation_id` — the row whose `token` is actually in the URL the candidate has. This distinction matters for `single`-type links: on first submission the **parent's** `status` flips to `submitted` so the link can't be reused, while the **child** is what admins see/approve/reject in the applications list. When `OnboardingController::reject()` rejects a child application, it also resets its parent back to `status: 'pending'` with a fresh `expires_at` (720h out) — so a rejected candidate can reuse the exact same URL to fix and resubmit, rather than the link staying dead or expiring on them. Keep this reopen-on-reject step in mind if you ever touch the reject flow or add a new terminal state for applications.

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

Deployed to Serverbyt shared hosting (domain `pga1gurgaon.in`). No SSH/composer access there, which shapes the whole layout:

- `~/pga1/` (Laravel app: `app/`, `vendor/`, `.env`, etc.) and `~/public_html/` (web root) are **siblings**, not parent/child — `vendor/` has to be zipped locally and uploaded, since `composer install` can't run on the server.
- `public_html/index.php` is a **hand-modified** Laravel entry point (not the stock one): it sets `$basePath = dirname(__DIR__) . '/pga1'`, then calls `$app->useStoragePath($basePath.'/storage')` **and** `$app->usePublicPath(__DIR__)`. The `usePublicPath` call is required — without it `public_path()` resolves to the nonexistent `pga1/public/` and any route touching it (e.g. onboarding pages) 500s.
- No `artisan storage:link` either — the `public_html/storage` → `pga1/storage/app/public` symlink is created by uploading a one-off `symlink.php` to `public_html/`, hitting it once in the browser, then **deleting it immediately** (it's a live symlink-creation script, a security risk to leave up).
- `.htaccess` in `public_html/` needs `RewriteRule ^$ index.html [L]` placed *before* the catch-all front-controller rule — otherwise `/` gets routed through Laravel's API and shows raw JSON instead of the static landing page.
- `config/cors.php` uses `allowed_origins => ['*']` in production (frontend and API share the domain, so this is low-risk here — don't assume it's safe to copy elsewhere).
- The DB host is a specific Serverbyt/StackCP hostname from the panel (e.g. `sdb-81.hosting.stackcp.net`), never `localhost`.
- Dashboard/website JS must never hardcode `127.0.0.1` — always go through the `API_BASE` auto-detect pattern (see Frontend dashboards above). A past incident: `admin.html` had two hardcoded `http://127.0.0.1:8000/...` document-preview URLs that broke in production; fixed by routing through `API_BASE`. Grep for `127.0.0.1` in dashboard HTML/JS before deploying if touching those files.
- If exporting the DB from Windows PowerShell for manual import via phpMyAdmin, use `mysqldump ... --result-file=path.sql` rather than `>` redirection — PowerShell's default UTF-16 output on `>` corrupts the SQL file on import.

See `SERVERBYT-DEPLOYMENT-GUIDE.md` (clean step-by-step reference) and `serverByteSetup.md` (the actual dated deployment log, with real hostnames/paths and a "Problems Encountered & Fixes" section) in the repo root for full procedure and history — consult these before changing anything touching `public_html`, `.htaccess`, `index.php`, or `config/cors.php`.
