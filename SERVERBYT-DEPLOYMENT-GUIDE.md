# PG A1 — ServerByt Deployment Guide

Complete step-by-step guide to deploy this project on ServerByt shared hosting.

---

## Project Structure (What Gets Deployed)

```
/ (home directory on ServerByt)
│
├── pga1/                      ← Laravel app (OUTSIDE public_html, secure)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/                ← MUST upload (no SSH = no composer install)
│   ├── .env                   ← Production environment config
│   ├── artisan
│   └── composer.json
│
└── public_html/               ← Web-accessible document root
    ├── index.php              ← Modified Laravel entry (points to ../pga1/)
    ├── index.html             ← PG public website (dynamic, fetches from API)
    ├── onboarding.html        ← New tenant onboarding form
    ├── verification.html      ← Existing tenant verification form
    ├── .htaccess              ← Apache URL rewriting
    ├── pg-data.json           ← Static fallback data
    ├── storage/               ← Symlink to pga1/storage/app/public (for photos)
    ├── images/                ← Website images
    ├── dashboard/
    │   ├── login.html
    │   ├── admin.html
    │   ├── super-admin.html
    │   ├── tenant.html
    │   └── js/
    │       ├── api.js         ← Change API_BASE before deploying!
    │       └── modal.js
    └── [PG photo folders]/
```

---

## Pre-Deployment Checklist

Before uploading, make these changes locally:

### 1. Update `api.js` API Base URL
Edit `backend/public/dashboard/js/api.js`:
```javascript
// Change FROM:
const API_BASE = 'http://127.0.0.1:8000/api/v1';

// Change TO:
const API_BASE = '/api/v1';
```

### 2. Update CORS config
Edit `backend/config/cors.php`:
```php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
],
```

### 3. Update `index.html` API URL
Search for `API_BASE_URL` in `index.html`:
```javascript
// Change FROM:
const API_BASE_URL = 'http://127.0.0.1:8000/api/v1';

// Change TO:
const API_BASE_URL = '/api/v1';
```

---

## Step 1: Create MySQL Database

1. ServerByt panel → **MySQL Databases**
2. Create database: `pga1_db`
3. Create user: `pga1_user` → set strong password
4. Add user to database → grant ALL PRIVILEGES
5. Note the **DB Host** (e.g., `sdb-82.hosting.stackcp.net`) — NOT `localhost`

---

## Step 2: Upload Laravel App to ~/pga1/

From `backend/` folder, zip **everything EXCEPT `public/`**:
```
app/, bootstrap/, config/, database/, resources/, routes/,
storage/, tests/, vendor/, artisan, composer.json, composer.lock, .env,
php-server.ini
```

Upload to `~/pga1/` via File Manager → Extract.

---

## Step 3: Upload Public Files to public_html/

Zip `backend/public/` contents → upload to `public_html/` → Extract.

Delete default `index.html` from ServerByt if present.

### Create `public_html/index.php`:
```php
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$basePath = dirname(__DIR__) . '/pga1';
if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$app->useStoragePath($basePath . '/storage');
$app->handleRequest(Request::capture());
```

---

## Step 4: Configure .env

Edit `~/pga1/.env`:
```env
APP_NAME="PG A1"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_FROM_LOCAL_ENV
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=sdb-82.hosting.stackcp.net
DB_PORT=3306
DB_DATABASE=yourprefix_pga1_db
DB_USERNAME=yourprefix_pga1_user
DB_PASSWORD=YourStrongPassword

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

---

## Step 5: Set Permissions

| Path | Permission |
|------|-----------|
| `pga1/storage/` | 775 (recursive) |
| `pga1/bootstrap/cache/` | 775 (recursive) |

Create these if missing:
```
storage/app/public/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
```

### Create storage symlink:
If SSH available:
```bash
cd public_html
ln -s ../pga1/storage/app/public storage
```
If no SSH: Create `public_html/storage/` folder and manually copy uploaded files there (or ask hosting support).

---

## Step 6: Import Database

### Option A: SSH available
```bash
cd ~/pga1
php artisan migrate --force
```

### Option B: No SSH
1. Locally run: Export database from phpMyAdmin
2. On server: phpMyAdmin → Import → upload SQL file

### Create Super Admin:
In phpMyAdmin, run:
```sql
INSERT INTO users (name, mobile, password, role, is_active, created_at, updated_at)
VALUES ('Super Admin', '9999999999',
'$2y$12$YOUR_BCRYPT_HASH', 'super_admin', 1, NOW(), NOW());
```

Generate hash by visiting `https://yourdomain.com/hash.php` (create temporarily):
```php
<?php echo password_hash("admin123", PASSWORD_DEFAULT);
```
**Delete `hash.php` immediately after.**

---

## Step 7: PHP Configuration

Create `~/pga1/.user.ini` (or ask hosting to set):
```ini
upload_max_filesize = 50M
post_max_size = 100M
max_execution_time = 300
memory_limit = 512M
```

---

## Step 8: Verify Deployment

| Test | URL | Expected |
|------|-----|----------|
| API | `/api/v1/public/pg-locations` | JSON response |
| Website | `/` | PG A1 landing page |
| Login | `/dashboard/login.html` | Login form |
| Onboarding | Generated via admin | Onboarding form |

Login: Mobile `9999999999`, Password: what you set.

---

## Post-Deployment: Admin Setup

Once deployed, the Super Admin should:

1. **Edit PG locations** → Click each PG card on dashboard → Add:
   - Photos (upload from dashboard)
   - WhatsApp number
   - Google Maps link + embed
   - Amenities (AC, Wi-Fi, Food, etc.)
   - Sharing type, meals info, tags
   - Display rent

2. **Add Rooms & Beds** → Rooms & Beds section

3. **Create Admin accounts** → Assign to PGs

4. **Generate onboarding links** → Share with tenants

The public website (`index.html`) automatically shows all PG data from the database — no code changes needed after initial setup.

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| 500 Error | Check `pga1/storage/logs/laravel.log`. Usually DB credentials or permissions. |
| DB connection refused | Use exact host from panel, not `localhost` |
| Class not found | `vendor/` not fully uploaded |
| API 404 | Check `.htaccess` and `index.php` exist in `public_html/` |
| CORS errors | Update `config/cors.php` with your domain |
| Permission denied | `storage/` and `bootstrap/cache/` need 775 |
| Login fails | Verify `api.js` has `/api/v1` (not localhost URL) |
| File upload fails | Check `.user.ini` upload limits, `storage/` writable |
| Photos not showing | Storage symlink missing (`public_html/storage/`) |
| Onboarding 413 error | PHP `upload_max_filesize` too low |

---

## Security Checklist

- [ ] `APP_DEBUG=false`
- [ ] `.env` is in `~/pga1/` (not web-accessible)
- [ ] `vendor/` is in `~/pga1/` (not web-accessible)
- [ ] Deleted `hash.php`
- [ ] Strong DB password
- [ ] SSL/HTTPS enabled
- [ ] Remove `ValidatePostSize` from Kernel (already done in code)

---

## Quick Reference

| Page | URL |
|------|-----|
| Public Website | `https://yourdomain.com` |
| Dashboard Login | `https://yourdomain.com/dashboard/login.html` |
| Super Admin | `https://yourdomain.com/dashboard/super-admin.html` |
| Admin Panel | `https://yourdomain.com/dashboard/admin.html` |
| Tenant Dashboard | `https://yourdomain.com/dashboard/tenant.html` |
| API | `https://yourdomain.com/api/v1/` |
| New Tenant Onboarding | Generated links via admin |
| Existing Tenant Verification | Generated links via admin (type: Existing) |

---

## Default Credentials

| Role | Mobile | Password |
|------|--------|----------|
| Super Admin | `9999999999` | `admin123` (change after first login) |

---

*Last updated: July 2026*
