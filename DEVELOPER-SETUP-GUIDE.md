# PG A1 Management System — Developer Setup Guide

This guide helps a new developer get the project running on their machine from scratch.

---

## Project Overview

- **Backend:** Laravel 12 (PHP 8.2) + MySQL + Sanctum (token auth)
- **API Base:** `http://localhost:8000/api/v1/`
- **Dashboards:** Static HTML + Tailwind CSS + vanilla JS (no React/Vue)
- **Public Website:** `index.html` (static, fetches PG data from API)

---

## Folder Structure

```
PG A1 Laravel/
├── index.html                  ← Public website (PG listings)
├── pg-data.json                ← Static fallback data for website
├── images/                     ← Website images
├── design-guide.md             ← Brand colors, fonts, spacing
├── backend/
│   ├── app/                    ← Laravel app (Models, Controllers, Services, Middleware)
│   ├── bootstrap/app.php       ← Route & middleware registration
│   ├── config/                 ← Laravel config (cors, sanctum, etc.)
│   ├── database/
│   │   ├── migrations/         ← 20 migration files
│   │   ├── seeders/            ← SuperAdmin + PgLocation seeders
│   │   └── schema.sql          ← Raw SQL reference
│   ├── routes/api.php          ← All API routes (/api/v1/...)
│   ├── dashboards/             ← Admin dashboard HTML files
│   │   ├── login.html
│   │   ├── super-admin.html
│   │   ├── admin.html
│   │   ├── tenant.html
│   │   └── js/api.js           ← Shared API client module
│   ├── public/                 ← Laravel public (served by web server)
│   │   └── dashboard/          ← Deployed dashboard files
│   ├── .env                    ← Environment config (DO NOT COMMIT)
│   ├── artisan                 ← Laravel CLI
│   ├── composer.json
│   └── vendor/                 ← PHP dependencies (DO NOT COMMIT)
```

---

## Prerequisites

| Software | Version | Download |
|----------|---------|----------|
| XAMPP | 8.2.12 | https://www.apachefriends.org/download.html |
| Composer | Latest | https://getcomposer.org/download/ |
| Git | Latest | https://git-scm.com/download/win |
| VS Code / Kiro | Latest | Your choice |

---

## Step-by-Step Setup

### Step 1: Install XAMPP

1. Download XAMPP 8.2.12 from https://www.apachefriends.org/download.html
2. During install, only check: **Apache, MySQL, PHP, phpMyAdmin**
3. Install to default path: `C:\xampp`
4. After install, open **XAMPP Control Panel** and start **MySQL**

### Step 2: Add PHP to System PATH

1. Press `Win + S` → search **"Environment Variables"** → click "Edit the system environment variables"
2. Click **"Environment Variables"** button
3. Under **"System variables"** → find `Path` → click **Edit**
4. Click **New** → type `C:\xampp\php`
5. Click **New** → type `C:\xampp\mysql\bin`
6. Click **OK** → **OK** → **OK**
7. Open a **NEW** terminal (old ones won't see the change)
8. Verify:
   ```
   php -v
   ```
   Should show: `PHP 8.2.12`

### Step 3: Install Composer

1. Download from https://getcomposer.org/Composer-Setup.exe
2. Run the installer — it will auto-detect PHP at `C:\xampp\php\php.exe`
3. Complete the installer
4. Verify in a **new** terminal:
   ```
   composer --version
   ```

### Step 4: Create the Database

1. Open XAMPP Control Panel → Start **MySQL**
2. Open browser → go to http://localhost/phpmyadmin
3. Click **"New"** in the left sidebar
4. Database name: `pga1_management`
5. Collation: `utf8mb4_unicode_ci`
6. Click **Create**

### Step 5: Extract & Setup the Project

1. Extract the zip file to any location (e.g. `E:\PG A1 Laravel\`)
2. Open terminal and navigate to the backend folder:
   ```
   cd "E:\PG A1 Laravel\backend"
   ```

### Step 6: Install PHP Dependencies

```bash
composer install
```

> **Note:** If the `vendor/` folder is already in the zip, you can skip this step. But it's good practice to run it anyway to ensure everything is up to date.

### Step 7: Configure Environment

The `.env` file should already be present. If not, copy from example:
```bash
copy .env.example .env
```

Open `.env` and verify these settings:
```env
APP_NAME="PG A1 Management"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pga1_management
DB_USERNAME=root
DB_PASSWORD=
```

> **Important:** XAMPP MySQL default has no password for root. If you set one during install, update `DB_PASSWORD` here.

### Step 8: Generate App Key

```bash
php artisan key:generate
```

### Step 9: Run Database Migrations

```bash
php artisan migrate
```

This creates all 21 tables. You should see output like:
```
2024_01_01_000001_create_users_table .................. DONE
2024_01_01_000002_create_pg_locations_table ........... DONE
...
```

### Step 10: Seed the Database

```bash
php artisan db:seed
```

This creates:
- **Super Admin** account (mobile: `9999999999`, password: `SuperAdmin@123`)
- **5 PG Locations** (Shanti Nagar, Jharsa Village, Sector 46, Saraswati Vihar, 1BHK)

### Step 11: Deploy Dashboards to Public Folder

```bash
mkdir public\dashboard
mkdir public\dashboard\js
copy dashboards\login.html public\dashboard\
copy dashboards\super-admin.html public\dashboard\
copy dashboards\admin.html public\dashboard\
copy dashboards\tenant.html public\dashboard\
copy dashboards\js\api.js public\dashboard\js\
```

### Step 12: Start the Server

```bash
php artisan serve
```

You should see:
```
INFO  Server running on [http://127.0.0.1:8000].
```

**Keep this terminal open.** The server runs here.

---

## Testing

### Open in Browser

| Page | URL |
|------|-----|
| API Health | http://127.0.0.1:8000 |
| Public PG API | http://127.0.0.1:8000/api/v1/public/pg-locations |
| Login Page | http://127.0.0.1:8000/dashboard/login.html |

### Login Credentials

| Role | Mobile | Password |
|------|--------|----------|
| Super Admin | `9999999999` | `SuperAdmin@123` |

After login, you'll be redirected to the Super Admin dashboard automatically.

### Test API with curl/Postman

```bash
# Public (no auth)
curl http://127.0.0.1:8000/api/v1/public/pg-locations

# Login
curl -X POST http://127.0.0.1:8000/api/v1/login ^
  -H "Content-Type: application/json" ^
  -d "{\"mobile\":\"9999999999\",\"password\":\"SuperAdmin@123\"}"

# Authenticated (use token from login response)
curl http://127.0.0.1:8000/api/v1/super-admin/dashboard ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE" ^
  -H "Accept: application/json"
```

---

## Available API Endpoints

### Public (No Auth)
- `GET /api/v1/public/pg-locations` — PG listings with availability
- `GET /api/v1/onboarding/{token}/validate` — Check onboarding link
- `POST /api/v1/onboarding/{token}/submit` — Submit onboarding form

### Auth
- `POST /api/v1/login` — Login (returns token)
- `POST /api/v1/logout` — Logout (revokes token)
- `GET /api/v1/me` — Current user info

### Tenant (role: tenant)
- `GET /api/v1/tenant/dashboard`
- `GET /api/v1/tenant/profile`
- `GET /api/v1/tenant/rents`
- `POST /api/v1/tenant/payments` — Upload payment screenshot

### Admin (role: admin, super_admin)
- `GET /api/v1/admin/tenants`
- `GET /api/v1/admin/payments`
- `POST /api/v1/admin/payments/{id}/verify`
- `POST /api/v1/admin/payments/{id}/reject`
- `GET /api/v1/admin/rooms`
- `POST /api/v1/admin/rooms`
- `GET /api/v1/admin/beds`
- `POST /api/v1/admin/beds`
- `PUT /api/v1/admin/beds/{id}`
- `GET /api/v1/admin/pg-locations`
- `POST /api/v1/admin/onboarding/invite`
- `GET /api/v1/admin/onboarding/applications`
- `POST /api/v1/admin/onboarding/{id}/approve`
- `POST /api/v1/admin/rents/generate`

### Super Admin (role: super_admin)
- `GET /api/v1/super-admin/dashboard`
- `GET /api/v1/super-admin/admins`
- `POST /api/v1/super-admin/admins`
- `POST /api/v1/super-admin/admins/{id}/assign-pg`
- `POST /api/v1/super-admin/pg-locations`
- `PUT /api/v1/super-admin/pg-locations/{id}`

---

## Common Issues & Fixes

### "XAMPP MySQL won't start"
Port 3306 may be occupied. Open XAMPP → Config → change MySQL port, or kill the conflicting process.

### "php is not recognized"
You didn't add `C:\xampp\php` to PATH, or you need to open a **new** terminal window.

### "SQLSTATE Connection refused"
MySQL isn't running. Open XAMPP Control Panel → Start MySQL.

### "Class not found" errors
Run `composer dump-autoload` in the backend folder.

### "CSRF token mismatch (419)"
This shouldn't happen with current setup. If it does, check that `bootstrap/app.php` does NOT have `$middleware->statefulApi()`.

### "Route not found (404)"
Make sure `bootstrap/app.php` has the `api:` line:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',  // ← this line is required
    ...
)
```

### Dashboard shows "Loading..." forever
Check browser console (F12) for errors. Usually means:
- Server isn't running (`php artisan serve`)
- Token expired → clear localStorage and re-login

---

## Development Workflow

### Adding a new feature:
1. Create migration: `php artisan make:migration create_table_name`
2. Create model: `php artisan make:model ModelName`
3. Create controller: `php artisan make:controller Api/ControllerName`
4. Add route in `routes/api.php`
5. Test with Postman or curl

### Resetting the database:
```bash
php artisan migrate:fresh --seed
```
⚠️ This drops ALL tables and re-creates everything. Use only in development.

### Checking registered routes:
```bash
php artisan route:list
```

### Clearing cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Files You Should NOT Include in Git/Zip

Add these to `.gitignore`:
```
/vendor/
/node_modules/
.env
/storage/logs/*
/public/hot
```

> The `vendor/` folder is 50MB+. In production, run `composer install` instead.

---

## What's Next (TODO for Development)

1. **Add Rooms & Beds** via Admin dashboard (needed for live availability)
2. **Electricity Billing** controller endpoints
3. **Document Verification** workflow (admin approves/rejects uploaded docs)
4. **Concern/Ticket** system (tenant raises, admin resolves)
5. **Offboarding** flow
6. **Connect public website** (`index.html`) to live API (already configured, just needs server running)
7. **Deploy to production** server

---

## Contact

For questions about the codebase, refer to:
- `backend/TASK-LIST.md` — Full feature checklist with status
- `design-guide.md` — Brand colors, fonts, spacing rules
- `backend/database/schema.sql` — Complete database schema reference
