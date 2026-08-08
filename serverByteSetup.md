# PG A1 — Serverbyt Deployment Setup (Step-by-Step Record)

Complete record of deploying PG A1 Laravel project to Serverbyt shared hosting.  
**Date:** 8 August 2026  
**Domain:** pga1gurgaon.in  
**Hosting:** Serverbyt (Blaze package, Autoscaling Linux, London UK)

---

## Table of Contents

1. [Serverbyt Account Overview](#1-serverbyt-account-overview)
2. [Domain DNS Configuration](#2-domain-dns-configuration)
3. [Add Domain to Serverbyt](#3-add-domain-to-serverbyt)
4. [Create MySQL Database](#4-create-mysql-database)
5. [Upload Laravel App Files](#5-upload-laravel-app-files)
6. [Upload Public Files](#6-upload-public-files)
7. [Configure .env File](#7-configure-env-file)
8. [Update index.php](#8-update-indexphp)
9. [PHP Version Change](#9-php-version-change)
10. [SSL & Nameserver Configuration](#10-ssl--nameserver-configuration)
11. [Storage Symlink Setup](#11-storage-symlink-setup)
12. [Problems Encountered & Fixes](#12-problems-encountered--fixes)
13. [Final Verification](#13-final-verification)
14. [Server Details Quick Reference](#14-server-details-quick-reference)

---

## 1. Serverbyt Account Overview

<!-- INSERT SCREENSHOT: Serverbyt cPanel dashboard showing all sections -->

**Account Summary:**
| Field | Value |
|-------|-------|
| Primary Domain | pga1gurgaon.in (changed from templates1.shop) |
| Package Type | Blaze |
| Platform | Autoscaling Linux |
| Location | London, UK |
| Home Path | /home/sites/5b/7/7a6d5dba5e/ |
| IP Address | 185.151.30.162 |
| IPv6 Address | 2a07:7800::162 |
| Incoming Mail Server | imap.pga1gurgaon.in |
| Outgoing Mail Server | smtp.pga1gurgaon.in |

---

## 2. Domain DNS Configuration

### Problem:
Domain `pga1gurgaon.in` was pointing to GitHub Pages (managed via Hostinger DNS).

### Old DNS Records (to be removed):
| Type | Name | Value |
|------|------|-------|
| CNAME | www | ravi0818k-ui.github.io |
| A | @ | 185.199.108.153 |
| A | @ | 185.199.109.153 |
| A | @ | 185.199.110.153 |
| A | @ | 185.199.111.153 |

<!-- INSERT SCREENSHOT: Hostinger DNS page showing old GitHub Pages records -->

### Action Taken:
1. Logged into Hostinger → Domain portfolio → pga1gurgaon.in → DNS/Nameservers
2. Deleted all GitHub Pages A records and CNAME record
3. Added new A record:
   - Type: A
   - Name: @
   - Value: `185.151.30.162` (Serverbyt IP)
   - TTL: 14400

<!-- INSERT SCREENSHOT: Hostinger DNS after adding Serverbyt A record -->

### Result:
Domain started resolving to Serverbyt within 15 minutes.

---

## 3. Add Domain to Serverbyt

### Steps:
1. Serverbyt cPanel → Domain Names → Domains
2. `pga1gurgaon.in` was already listed (auto-added or pre-configured)
3. Document Root: `public_html`
4. Clicked "Make Primary" to set it as the primary domain

<!-- INSERT SCREENSHOT: Serverbyt Manage Domains page showing pga1gurgaon.in -->

### Result:
- pga1gurgaon.in set as primary domain
- FTP details updated to use pga1gurgaon.in

---

## 4. Create MySQL Database

### Steps:
1. Serverbyt cPanel → Web Tools → MySQL Databases
2. Entered database name: `pga1db`
3. Clicked "Create Database"
4. System auto-generated username and password

<!-- INSERT SCREENSHOT: MySQL Databases page after creation showing the new database -->

### Database Details:
| Field | Value |
|-------|-------|
| Server | sdb-81.hosting.stackcp.net |
| Database Name | pga1db-35303837f495 |
| Username | pga1db-35303837f495 |
| Password | (saved securely — set during creation) |
| Max Size | 1024 MB |

**Note:** Serverbyt creates database and user with the same name automatically.

---

## 5. Upload Laravel App Files

### Preparation (Local):
Created `laravel-app.zip` from `backend/` folder containing:
- ✅ app/
- ✅ bootstrap/
- ✅ config/
- ✅ database/
- ✅ resources/
- ✅ routes/
- ✅ storage/
- ✅ tests/
- ✅ vendor/ (MUST include — no SSH for composer install)
- ✅ .env, .env.example, .user.ini
- ✅ artisan, composer.json, composer.lock
- ✅ php-server.ini, phpunit.xml

**Excluded:**
- ❌ public/ (goes to public_html separately)
- ❌ dashboards/ (goes to public_html/dashboard)

### Upload Steps:
1. Serverbyt File Manager → Home directory
2. Created new folder: `pga1`
3. Opened `pga1/` folder
4. Uploaded `laravel-app.zip`
5. Right-click → Extract/Unzip
6. Deleted the zip file after extraction

<!-- INSERT SCREENSHOT: pga1 folder contents after extraction -->

### Result:
All Laravel app files in `/home/sites/5b/7/7a6d5dba5e/pga1/`

---

## 6. Upload Public Files

### Preparation (Local):
Created `public-files.zip` from `backend/public/` contents:
- ✅ dashboard/ (login.html, admin.html, super-admin.html, tenant.html, js/)
- ✅ images/
- ✅ PGA1 1BHK saraswati vihar-done/
- ✅ PGA1 jharsa village-done/
- ✅ PGA1 Sarswati Vihar PG-done/
- ✅ PGA1 Sec 46-done/
- ✅ .htaccess
- ✅ favicon.ico
- ✅ index.html
- ✅ index.php
- ✅ onboarding.html
- ✅ pg-data.json
- ✅ robots.txt
- ✅ verification.html

**Excluded:**
- ❌ public/ (nested duplicate)
- ❌ storage (symlink — handled separately)

### Upload Steps:
1. Serverbyt File Manager → `public_html/`
2. Deleted default Serverbyt files
3. Uploaded `public-files.zip`
4. Right-click → Extract/Unzip
5. Deleted the zip file after extraction

<!-- INSERT SCREENSHOT: public_html folder contents after extraction -->

### Result:
All web-accessible files in `public_html/`

---

## 7. Configure .env File

### Steps:
1. File Manager → `pga1/` → Edit `.env`
2. Updated all values for production

<!-- INSERT SCREENSHOT: .env file in Serverbyt editor -->

### Final .env content:
```env
APP_NAME="PG A1"
APP_ENV=production
APP_KEY=base64:9eIpdcNlrs3DqcW/WB+gZFakrnIoSsENQ+qUc1D8THk=
APP_DEBUG=false
APP_URL=https://pga1gurgaon.in

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=sdb-81.hosting.stackcp.net
DB_PORT=3306
DB_DATABASE=pga1db-35303837f495
DB_USERNAME=pga1db-35303837f495
DB_PASSWORD=<your-password-here>

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=pga1gurgaon.in,www.pga1gurgaon.in
```

### Key Changes from Local:
| Setting | Local | Production |
|---------|-------|-----------|
| APP_ENV | local | production |
| APP_DEBUG | true | false |
| APP_URL | http://localhost:8000 | https://pga1gurgaon.in |
| DB_HOST | 127.0.0.1 | sdb-81.hosting.stackcp.net |
| DB_DATABASE | pga1_management | pga1db-35303837f495 |
| DB_USERNAME | root | pga1db-35303837f495 |
| DB_PASSWORD | (empty) | (strong password) |
| SANCTUM_STATEFUL_DOMAINS | localhost,... | pga1gurgaon.in,www.pga1gurgaon.in |

---

## 8. Update index.php

### Problem:
Default `index.php` uses `__DIR__.'/../'` which doesn't point to `pga1/` correctly since `public_html/` and `pga1/` are siblings.

### Steps:
1. File Manager → `public_html/` → Edit `index.php`
2. Replaced entire content

<!-- INSERT SCREENSHOT: Updated index.php in editor -->

### Final index.php:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__) . '/pga1';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath . '/bootstrap/app.php';

$app->useStoragePath($basePath . '/storage');
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

### Key points:
- `$basePath = dirname(__DIR__) . '/pga1'` — explicitly points to pga1 folder
- `$app->useStoragePath(...)` — ensures storage path is correct
- `$app->usePublicPath(__DIR__)` — tells Laravel that public_html is the public directory (needed for `public_path()` to work)

---

## 9. PHP Version Change

### Problem:
Error when hitting API: "Composer detected issues in your platform: Your Composer dependencies require a PHP version >= 8.2.0"

<!-- INSERT SCREENSHOT: PHP version error in browser -->

### Steps:
1. Serverbyt cPanel → Web Tools → Change PHP Version
2. Already showing PHP 8.2 but clicked "Change PHP Version" to refresh
3. API started working after refresh

<!-- INSERT SCREENSHOT: Change PHP Version page -->

### Result:
API endpoint returned JSON successfully.

---

## 10. SSL & Nameserver Configuration

### Problem:
SSL certificate couldn't be issued because domain was using Hostinger's parking nameservers (`apollo.dns-parking.com`, `athena.dns-parking.com`), not Serverbyt's.

<!-- INSERT SCREENSHOT: Free SSL Certificate page showing "No SSL Active" -->

### Steps:
1. Hostinger → Domain → pga1gurgaon.in → DNS/Nameservers
2. Selected "Change nameservers"
3. Replaced:
   - `apollo.dns-parking.com` → `ns1.stackcp.com`
   - `athena.dns-parking.com` → `ns2.stackcp.com`
4. Confirmed the warning popup
5. Saved changes

<!-- INSERT SCREENSHOT: Hostinger nameserver change confirmation -->
<!-- INSERT SCREENSHOT: "Nameservers changed!" success popup -->

### Result:
- Nameservers updated to Serverbyt's
- SSL will auto-activate once propagation completes (up to 24 hours)
- "Force HTTPS" already enabled on Serverbyt — will auto-redirect once SSL is active

### Serverbyt Nameservers:
```
ns1.stackcp.com
ns2.stackcp.com
```

---

## 11. Storage Symlink Setup

### Problem:
No SSH access to run `php artisan storage:link`. Tenant photos and uploaded documents need to be accessible via web.

### Solution:
Created a temporary PHP script to create the symlink.

### Steps:
1. File Manager → `public_html/` → Create new file: `symlink.php`
2. Added this content:

```php
<?php
$target = dirname(__DIR__) . '/pga1/storage/app/public';
$link = __DIR__ . '/storage';

if (is_dir($link) && !is_link($link)) {
    rmdir($link);
}
if (is_link($link)) {
    unlink($link);
}

if (symlink($target, $link)) {
    echo "✅ Symlink created successfully!<br>";
    echo "Target: $target<br>";
    echo "Link: $link";
} else {
    echo "❌ Failed to create symlink.<br>";
    echo "Target: $target<br>";
    echo "Link: $link<br>";
    echo "You may need to ask hosting support to create it.";
}
```

3. Visited `http://pga1gurgaon.in/symlink.php`
4. Got success message:
   - Target: `/home/sites/5b/7/7a6d5dba5e/pga1/storage/app/public`
   - Link: `/home/sites/5b/7/7a6d5dba5e/public_html/storage`
5. **Deleted `symlink.php` immediately** (security)

<!-- INSERT SCREENSHOT: symlink.php success message -->

### Result:
`public_html/storage` → `pga1/storage/app/public` (uploaded files now accessible via web)

---

## 12. Problems Encountered & Fixes

### Problem 1: Homepage Showing JSON Instead of Website

**Symptom:** Visiting `https://pga1gurgaon.in` showed Laravel's API JSON response instead of the landing page.

**Cause:** `.htaccess` was routing ALL requests through `index.php` (Laravel), which matched the `/` route returning JSON.

**Fix:** Added a rule to `.htaccess` to serve `index.html` for the root URL:

```apache
# Serve index.html for root URL
RewriteRule ^$ index.html [L]
```

**Full .htaccess:**
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Serve index.html for root URL
    RewriteRule ^$ index.html [L]

    # Send Requests To Front Controller (only if not a real file/directory)
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### Problem 2: Database Import Encoding Error

**Symptom:** Importing `.sql` file in phpMyAdmin gave "716 errors — Unexpected character at position 1, 3, 5, 7..." (every odd position).

**Cause:** Windows PowerShell outputs files in UTF-16 encoding by default when using `>` redirect.

**Fix:** Used `--result-file` flag which bypasses shell encoding:
```cmd
mysqldump -u root --default-character-set=utf8 pga1_management --result-file=E:\pga1_export.sql
```

**Result:** Import succeeded — 252 queries executed.

---

### Problem 3: PHP Version Error

**Symptom:** "Composer detected issues in your platform: Your Composer dependencies require a PHP version >= 8.2.0"

**Cause:** Server was on a PHP version below 8.2.

**Fix:** Serverbyt cPanel → Web Tools → Change PHP Version → Selected PHP 8.2 → Clicked "Change PHP Version"

---

### Problem 4: Onboarding Page 500 Error

**Symptom:** Visiting `/onboarding/{token}` gave a 500 Internal Server Error.

**Cause:** Laravel's `public_path()` was resolving to `pga1/public/` (which doesn't exist) instead of `public_html/`.

**Fix:** Added `$app->usePublicPath(__DIR__);` to `public_html/index.php`.

---

### Problem 5: Document Preview "Failed to load"

**Symptom:** Clicking on document previews (selfie, aadhaar, etc.) in Onboarding section showed "Failed to load document."

**Cause:** `admin.html` had hardcoded `http://127.0.0.1:8000/api/v1/admin/documents/${docId}/view` URLs that don't work in production.

**Fix:** Changed to use the `API_BASE` variable which auto-detects environment:

```javascript
// In api.js:
const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
  ? 'http://127.0.0.1:8000/api/v1'
  : '/api/v1';

// In admin.html (2 places):
const url = `${API_BASE}/admin/documents/${docId}/view`;
```

---

### Problem 6: "Approve & Assign" Button Not Working

**Symptom:** Clicking "Approve & Assign" did nothing. Console showed: `Uncaught (in promise) ReferenceError: Cannot access 'roomRef' before initialization`

**Cause:** Variable `roomRef` was used on line 900 but declared on line 914 (temporal dead zone with `const`).

**Fix:** Moved `roomRef` declaration before its usage:

```javascript
// BEFORE (broken):
const availableBeds = getBedsForPg(null, roomRef); // used here
// ... other code ...
const roomRef = app?.referral_code_used?.startsWith('ROOM:') ? ... ; // declared here

// AFTER (fixed):
const roomRef = app?.referral_code_used?.startsWith('ROOM:') ? ... ; // declared first
const availableBeds = getBedsForPg(null, roomRef); // then used
```

---

### Problem 7: CORS Configuration

**Symptom:** Potential CORS issues with API calls from frontend.

**Cause:** `config/cors.php` only had localhost origins listed.

**Fix:** Changed `allowed_origins` to `['*']` in `pga1/config/cors.php` since frontend and API are on the same domain anyway.

```php
'allowed_origins' => ['*'],
```

---

## 13. Final Verification

| Test | URL | Result |
|------|-----|--------|
| API Health | /api/v1/public/pg-locations | ✅ JSON response with PG data |
| Homepage | / | ✅ PG A1 landing page |
| Dashboard Login | /dashboard/login.html | ✅ Login form displays |
| Admin Panel | /dashboard/admin.html | ✅ Full admin functionality |
| Onboarding | /onboarding/{token} | ✅ Form loads correctly |
| Document View | Via admin panel | ✅ After URL fix |
| File Uploads | Via storage symlink | ✅ Symlink created |

<!-- INSERT SCREENSHOT: Admin panel working with tenant data -->
<!-- INSERT SCREENSHOT: Homepage loading correctly -->

---

## 14. Server Details Quick Reference

### Serverbyt cPanel Access
- URL: Via Serverbyt dashboard
- Primary Domain: pga1gurgaon.in

### FTP Details
| Field | Value |
|-------|-------|
| FTP Server | ftp.pga1gurgaon.in |
| Username | pga1gurgaon.in |
| Password | (set in panel) |

### Database Details
| Field | Value |
|-------|-------|
| Host | sdb-81.hosting.stackcp.net |
| Database | pga1db-35303837f495 |
| Username | pga1db-35303837f495 |
| Password | (set during creation) |
| phpMyAdmin | https://db.serverbyt.in |

### File Paths on Server
| What | Path |
|------|------|
| Home | /home/sites/5b/7/7a6d5dba5e/ |
| Laravel App | /home/sites/5b/7/7a6d5dba5e/pga1/ |
| Web Root | /home/sites/5b/7/7a6d5dba5e/public_html/ |
| Storage | /home/sites/5b/7/7a6d5dba5e/pga1/storage/ |
| Logs | /home/sites/5b/7/7a6d5dba5e/pga1/storage/logs/laravel.log |

### DNS / Nameservers
| Nameserver | Value |
|-----------|-------|
| NS1 | ns1.stackcp.com |
| NS2 | ns2.stackcp.com |

### URLs
| Page | URL |
|------|-----|
| Public Website | https://pga1gurgaon.in |
| Dashboard Login | https://pga1gurgaon.in/dashboard/login.html |
| Super Admin | https://pga1gurgaon.in/dashboard/super-admin.html |
| Admin Panel | https://pga1gurgaon.in/dashboard/admin.html |
| Tenant Dashboard | https://pga1gurgaon.in/dashboard/tenant.html |
| API Base | https://pga1gurgaon.in/api/v1/ |

### Default Login
| Role | Mobile | Password |
|------|--------|----------|
| Super Admin | 9999999999 | admin123 (change immediately!) |

---

## Post-Deployment TODO

- [ ] SSL certificate activation (waiting for nameserver propagation)
- [ ] Change super admin password
- [ ] Test all file uploads (tenant photos, payment screenshots)
- [ ] Verify onboarding full flow
- [ ] Set up email if needed
- [ ] Enable CDN/caching if required

---

*Document created: 8 August 2026*
