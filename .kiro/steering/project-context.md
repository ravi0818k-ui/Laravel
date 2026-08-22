# PG A1 Management System — Project Context

## Overview
PG A1 is a full-stack PG (Paying Guest) accommodation management system for properties in Gurugram, India. It has a Laravel 11 backend API and static HTML/JS frontend dashboards.

## Architecture
- **Backend:** Laravel 11 (PHP 8.2+) — REST API with Sanctum auth
- **Frontend:** Static HTML + Vanilla JS dashboards (no build step)
- **Database:** MySQL
- **Auth:** Laravel Sanctum token-based
- **File Storage:** Local disk (storage/app/)

## Key Directories
```
backend/                    ← Laravel app
backend/public/             ← Production public files (deployed to public_html)
backend/public/dashboard/   ← Admin, Super Admin, Tenant HTML dashboards
backend/public/dashboard/js/api.js  ← Shared API client (auto-detects local vs prod)
backend/dashboards/         ← Source copy of dashboards (for local dev with separate server)
backend/app/Services/       ← Business logic (RentService, ElectricityBillingService, etc.)
backend/routes/api.php      ← All API routes
backend/routes/web.php      ← Web routes (onboarding form serving)
index.html                  ← Public-facing PG website (root level, also in backend/public/)
```

## Roles
- **Super Admin** — manages PG locations, creates admin accounts
- **Admin** — manages tenants, rooms, beds, payments, onboarding, expenses for assigned PGs
- **Tenant** — views rent, submits payments, sees dashboard

## API Base URL Pattern
The frontend uses auto-detection:
```javascript
const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
  ? 'http://127.0.0.1:8000/api/v1'
  : '/api/v1';
```
All API calls must use `API_BASE` variable, never hardcoded localhost URLs.

## Local Development
```bash
cd backend
php artisan serve
```
- API: http://127.0.0.1:8000/api/v1
- Dashboard: http://127.0.0.1:8000/dashboard/login.html
- Default Super Admin: 9999999999 / admin123

## Production Deployment (Serverbyt)
- **Domain:** pga1gurgaon.in
- **Hosting:** Serverbyt shared hosting (Blaze package, Autoscaling Linux)
- **Server IP:** 185.151.30.162
- **Nameservers:** ns1.stackcp.com, ns2.stackcp.com (via Hostinger domain)

### Server File Structure
```
~/pga1/                     ← Laravel app (outside web root, secure)
~/public_html/              ← Web-accessible document root
~/public_html/index.php     ← Modified entry point (points to ../pga1/)
~/public_html/index.html    ← PG public website
~/public_html/dashboard/    ← All dashboard HTML files
~/public_html/storage/      ← Symlink to pga1/storage/app/public
```

### Database (Production)
- Host: sdb-81.hosting.stackcp.net
- Database: pga1db-35303837f495
- Username: pga1db-35303837f495
- phpMyAdmin: https://db.serverbyt.in

### Key Production Files
- `public_html/index.php` — Uses `dirname(__DIR__) . '/pga1'` as base path, includes `$app->usePublicPath(__DIR__)`
- `public_html/.htaccess` — Has `RewriteRule ^$ index.html [L]` to serve homepage, then routes other non-file requests to index.php
- `pga1/.env` — Production config with APP_DEBUG=false, correct DB credentials
- `pga1/config/cors.php` — `allowed_origins => ['*']`

## Important Gotchas
1. **Never hardcode localhost URLs** — always use `API_BASE` variable in JS
2. **Vendor folder must be uploaded** — no SSH/composer on Serverbyt
3. **Storage symlink** — created via temporary PHP script (no SSH for artisan storage:link)
4. **index.php needs usePublicPath** — because public_html is the web root, not pga1/public
5. **SQL export from Windows** — use `--result-file` flag to avoid UTF-16 encoding
6. **PHP 8.2 required** — must be set in Serverbyt cPanel
7. **`roomRef` variable order** — must be declared before `getBedsForPg()` call in admin.html

## Onboarding Flow
- Admin generates link → `/onboarding/{token}` (served via web.php route)
- Candidate fills form, uploads docs (selfie, aadhaar, voter ID)
- Documents stored in `storage/app/onboarding/{id}/`
- Admin reviews, approves/rejects from Onboarding section
- Approval creates user account, tenant profile, assigns bed

## Key Models
User, Tenant, PgLocation, Room, Bed, TenantBedAllocation, MonthlyRent, PaymentSubmission, Expense, ElectricityBill, ElectricityBillAllocation, OnboardingInvitation, TenantDocument, Note, ActivityLog, Referral

## Deployment Docs
- `SERVERBYT-DEPLOYMENT-GUIDE.md` — Original pre-deployment guide
- `serverByteSetup.md` — Actual step-by-step record of deployment with issues & fixes
