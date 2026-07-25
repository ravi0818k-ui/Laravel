# PG A1 — ServerByt Deployment Guide

Complete step-by-step guide to deploy this project on ServerByt shared hosting with a domain from Hostinger.

---

## Your Local Project Structure (What You Have)

```
E:\PG A1 Laravel\
├── backend/                    ← Laravel app (this is the main backend)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── dashboards/            ← Source dashboard HTML files (dev copies)
│   ├── public/                ← Laravel's public folder (THIS goes to public_html)
│   │   ├── index.php          ← Laravel entry point
│   │   ├── index.html         ← PG website (copy of root index.html)
│   │   ├── onboarding.html    ← Tenant onboarding form
│   │   ├── .htaccess          ← Apache rewrite rules
│   │   ├── pg-data.json
│   │   ├── images/
│   │   ├── PGA1 jharsa village-done/
│   │   ├── PGA1 Sarswati Vihar PG-done/
│   │   ├── PGA1 Sec 46-done/
│   │   ├── PGA1 1BHK saraswati vihar-done/
│   │   └── dashboard/
│   │       ├── login.html
│   │       ├── admin.html
│   │       ├── super-admin.html
│   │       ├── tenant.html
│   │       └── js/
│   │           ├── api.js
│   │           └── modal.js
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/                ← PHP dependencies (must upload this!)
│   ├── .env
│   ├── artisan
│   ├── composer.json
│   └── composer.lock
│
├── images/                    ← Website images (already inside backend/public/images too)
├── PGA1 jharsa village-done/  ← PG photos (already inside backend/public/ too)
├── PGA1 Sarswati Vihar PG-done/
├── PGA1 Sec 46-done/
├── PGA1 1BHK saraswati vihar-done/
├── index.html                 ← PG website (already inside backend/public/ too)
├── styles.css
└── pg-data.json
```

---

## What Goes Where on ServerByt

```
/ (your home directory on ServerByt)
│
├── pga1/                      ← Laravel app (OUTSIDE public_html, not web-accessible)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   └── composer.json
│
└── public_html/               ← Web-accessible document root
    ├── index.php              ← Modified Laravel entry (points to ../pga1/)
    ├── index.html             ← PG public website
    ├── onboarding.html        ← Tenant onboarding form
    ├── .htaccess              ← Apache URL rewriting
    ├── pg-data.json
    ├── images/
    ├── PGA1 jharsa village-done/
    ├── PGA1 Sarswati Vihar PG-done/
    ├── PGA1 Sec 46-done/
    ├── PGA1 1BHK saraswati vihar-done/
    └── dashboard/
        ├── login.html
        ├── admin.html
        ├── super-admin.html
        ├── tenant.html
        └── js/
            ├── api.js
            └── modal.js
```

---

## Step 1: Add Domain in ServerByt

1. Login to ServerByt control panel
2. Go to **Domain Names**
3. Add your domain (e.g. `pga1.yourdomain.com` or `yourdomain.com`)
4. Set it as **Primary Domain**
5. Document root: `public_html` (default)

---

## Step 2: Point Hostinger DNS to ServerByt

1. Login to **Hostinger** → go to **DNS / Nameservers**
2. Change nameservers to:
   ```
   ns1.serverbyt.in
   ns2.serverbyt.in
   ```
3. Save and wait 5–30 minutes for DNS propagation
4. **Verify:** Visit your domain in browser — should show ServerByt default page ("THIS SITE IS BRAND NEW")

---

## Step 3: Create MySQL Database

1. In ServerByt panel → **MySQL Databases**
2. Create a new database: `pga1_db`
3. Create a new user: `pga1_user` with a strong password
4. **Add user to database** → grant ALL PRIVILEGES
5. **IMPORTANT:** Note down these exact values from the panel:

| Field | Where to find it | Example |
|-------|-----------------|---------|
| **DB Host** | Database section / Account summary | `sdb-82.hosting.stackcp.net` |
| **DB Name** | Listed after creation (may have prefix) | `yourprefix_pga1_db` |
| **DB User** | Listed after creation (may have prefix) | `yourprefix_pga1_user` |
| **DB Password** | What you set | `YourStrongPass123!` |

> ⚠️ **DO NOT use `localhost`** as DB host. Use the exact hostname shown in your panel.

---

## Step 4: Upload Laravel App to ~/pga1/

### What to upload:

From your local `backend/` folder, take **everything EXCEPT the `public/` folder**:

```
From: E:\PG A1 Laravel\backend\
Upload these:
  app/
  bootstrap/
  config/
  database/
  resources/
  routes/
  storage/
  vendor/          ← IMPORTANT: Include this! No SSH = no composer install
  artisan
  composer.json
  composer.lock
  .env             ← Will edit after upload (Step 6)
```

### How to upload:

1. **Zip** all the above into one file (e.g. `pga1-app.zip`)
2. Open **File Manager** in ServerByt panel
3. Go to your **home directory** (one level above `public_html/`)
4. Create folder: `pga1`
5. Open `pga1/` folder
6. Click **Upload** → upload `pga1-app.zip`
7. Select the zip → click **Extract**
8. Delete the zip file after extraction

After extraction, `pga1/` should contain:
```
~/pga1/app/
~/pga1/bootstrap/
~/pga1/config/
~/pga1/vendor/
~/pga1/.env
~/pga1/artisan
... etc
```

---

## Step 5: Upload Public Files to public_html/

### What to upload:

Everything from your local `backend/public/` folder:

```
From: E:\PG A1 Laravel\backend\public\
Upload these to public_html/:
  .htaccess
  index.html
  onboarding.html
  pg-data.json
  robots.txt
  favicon.ico
  images/                         (entire folder)
  PGA1 jharsa village-done/       (entire folder)
  PGA1 Sarswati Vihar PG-done/    (entire folder)
  PGA1 Sec 46-done/               (entire folder)
  PGA1 1BHK saraswati vihar-done/ (entire folder)
  dashboard/                      (entire folder with login.html, admin.html, tenant.html, js/)
```

### How to upload:

1. **Zip** the entire `backend/public/` contents into `public-files.zip`
2. In File Manager, go to `public_html/`
3. Delete the default `index.html` if ServerByt put one there
4. Upload `public-files.zip`
5. Extract it
6. Delete the zip

### Create the modified index.php:

**DO NOT upload** the existing `backend/public/index.php` directly. Instead, create a NEW `public_html/index.php` with this content (it points to `../pga1/` instead of `../`):

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path to Laravel app (outside public_html for security)
$basePath = dirname(__DIR__) . '/pga1';

// Maintenance mode
if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader
require $basePath . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $basePath . '/bootstrap/app.php';

// Override the base path
$app->useStoragePath($basePath . '/storage');

$app->handleRequest(Request::capture());
```

In File Manager: go to `public_html/` → click **New File** → name it `index.php` → paste the code above → Save.

---

## Step 6: Configure .env on Server

In File Manager, open `~/pga1/.env` and edit it:

```env
APP_NAME="PG A1"
APP_ENV=production
APP_KEY=base64:COPY_YOUR_EXISTING_KEY_FROM_LOCAL_ENV
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=sdb-82.hosting.stackcp.net
DB_PORT=3306
DB_DATABASE=yourprefix_pga1_db
DB_USERNAME=yourprefix_pga1_user
DB_PASSWORD=YourStrongPass123!

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

**Replace:**
- `APP_KEY` → copy from your local `backend/.env` (the base64:... value)
- `DB_HOST` → exact host from Step 3
- `DB_DATABASE` → exact database name from Step 3
- `DB_USERNAME` → exact username from Step 3
- `DB_PASSWORD` → password you set in Step 3
- `APP_URL` → your actual domain
- `SANCTUM_STATEFUL_DOMAINS` → your domain without https://

---

## Step 7: Update api.js for Production

Edit `public_html/dashboard/js/api.js`:

**Change the first line from:**
```javascript
const API_BASE = 'http://127.0.0.1:8000/api/v1';
```

**To:**
```javascript
const API_BASE = '/api/v1';
```

---

## Step 8: Set Folder Permissions

In File Manager, right-click each folder → **Change Permissions** → set to `0775` → check "Recurse into subdirectories":

| Folder | Permission |
|--------|-----------|
| `pga1/storage/` | 775 (recursive) |
| `pga1/bootstrap/cache/` | 775 (recursive) |

Also make sure these exist inside `pga1/storage/`:
```
storage/app/
storage/app/public/
storage/framework/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
```

If any are missing, create them via File Manager.

---

## Step 9: Import Database (Create Tables)

### Option A: If you have SSH access
```bash
cd ~/pga1
php artisan migrate --force
php artisan db:seed --force
```

### Option B: No SSH (most shared hosting)

1. Locally, generate a SQL dump:
   ```
   cd backend
   php artisan schema:dump
   ```
   Or export from your local phpMyAdmin/MySQL.

2. In ServerByt panel → open **phpMyAdmin**
3. Select your database
4. Click **Import** tab
5. Upload your SQL file
6. Click **Go**

### Insert Super Admin manually:

First, generate a password hash. Create a temporary file `public_html/hash.php`:
```php
<?php echo password_hash("YourAdminPassword123", PASSWORD_DEFAULT);
```

Visit `https://yourdomain.com/hash.php` → copy the hash output.

Then in phpMyAdmin, run:
```sql
INSERT INTO users (name, mobile, password, role, is_active, created_at, updated_at)
VALUES ('Super Admin', '9999999999', 'PASTE_HASH_HERE', 'super_admin', 1, NOW(), NOW());
```

**Delete `hash.php` immediately after!**

### Insert PG Locations:

```sql
INSERT INTO pg_locations (name, city, address, is_active, created_at, updated_at) VALUES
('PG A1 – Shanti Nagar', 'Gurugram', 'Shanti Nagar, Sector 46', 1, NOW(), NOW()),
('PG A1 – Jharsa Village', 'Gurugram', 'Jharsa Village, Near Sector 39', 1, NOW(), NOW()),
('PG A1 – Sector 46', 'Gurugram', 'Sector 46, Gurugram', 1, NOW(), NOW()),
('PG A1 – Saraswati Vihar', 'Gurugram', 'Saraswati Vihar, Gurugram', 1, NOW(), NOW()),
('1BHK – Saraswati Vihar', 'Gurugram', 'Saraswati Vihar (1BHK)', 1, NOW(), NOW());
```

---

## Step 10: Update Laravel Bootstrap Path

Since we moved Laravel outside `public_html`, we need to tell Laravel where to find itself.

Edit `~/pga1/bootstrap/app.php` — find the line that creates the application and make sure base path is correct. In Laravel 12, it should auto-detect. If you get path errors, create/edit `~/pga1/bootstrap/app.php`:

Make sure it has:
```php
$app = \Illuminate\Foundation\Application::configure(basePath: __DIR__ . '/../')
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->statefulApi();
    })
    ->create();

return $app;
```

---

## Step 11: Verify Deployment

### Test 1: API works
Visit: `https://yourdomain.com/api/v1/public/pg-locations`
Should return JSON with PG locations.

### Test 2: Website works
Visit: `https://yourdomain.com`
Should show the PG A1 public website.

### Test 3: Dashboard works
Visit: `https://yourdomain.com/dashboard/login.html`
Login with: Mobile `9999999999`, Password: what you set.

### Test 4: Onboarding works
Visit: `https://yourdomain.com/onboarding.html`
Should show the onboarding form (will need a valid token to proceed).

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **500 Internal Server Error** | Check `~/pga1/storage/logs/laravel.log` for details. Usually wrong DB credentials or missing permissions. |
| **"Database connection refused"** | Wrong `DB_HOST` in `.env`. Use the host from your panel, NOT `localhost`. |
| **"Class not found"** | `vendor/` folder wasn't fully uploaded. Re-upload it. |
| **API returns 404** | `.htaccess` not working. Check mod_rewrite is enabled. Check `index.php` exists in `public_html/`. |
| **CORS errors** | Edit `~/pga1/config/cors.php` → set `'allowed_origins' => ['*']` or your domain. |
| **"Permission denied"** | Set `storage/` and `bootstrap/cache/` to 775 recursively. |
| **Dashboard login fails** | Check `api.js` has `const API_BASE = '/api/v1';` (not localhost). |
| **Blank page** | `APP_DEBUG=true` temporarily in `.env` to see actual error. Remember to set back to false! |

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `.env` is in `~/pga1/` (NOT in `public_html/`)
- [ ] `vendor/` is in `~/pga1/` (NOT in `public_html/`)
- [ ] Deleted `hash.php` after use
- [ ] Strong database password
- [ ] SSL/HTTPS enabled (get from ServerByt panel)

---

## Quick Reference URLs

| What | URL |
|------|-----|
| Public Website | `https://yourdomain.com` |
| Onboarding Form | `https://yourdomain.com/onboarding.html` |
| API Base | `https://yourdomain.com/api/v1/` |
| Dashboard Login | `https://yourdomain.com/dashboard/login.html` |
| Super Admin | `https://yourdomain.com/dashboard/super-admin.html` |
| Admin Panel | `https://yourdomain.com/dashboard/admin.html` |
| Tenant Dashboard | `https://yourdomain.com/dashboard/tenant.html` |

---

## Summary: What to Zip and Upload

### Zip 1: `pga1-app.zip` → Upload to `~/pga1/`
Everything from `backend/` **EXCEPT** the `public/` folder:
- `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`
- `artisan`, `composer.json`, `composer.lock`, `.env`

### Zip 2: `public-files.zip` → Upload to `public_html/`
Everything from `backend/public/`:
- `dashboard/`, `images/`, all PG photo folders
- `index.html`, `onboarding.html`, `pg-data.json`, `.htaccess`, `robots.txt`
- Then **manually create** `index.php` with the modified code from Step 5

---

*Last updated: July 2026*
