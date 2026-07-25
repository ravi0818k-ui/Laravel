# PG A1 Laravel — Installation Guide (Windows)

## Step 1: Install PHP 8.2+

### Option A: Download PHP directly (recommended)
1. Go to https://windows.php.net/download/
2. Download **PHP 8.2** → "VS16 x64 Non Thread Safe" → ZIP
3. Extract to `C:\php`
4. Add `C:\php` to your **System PATH**:
   - Press `Win + R` → type `sysdm.cpl` → Enter
   - Advanced → Environment Variables
   - Under "System variables" → find `Path` → Edit → Add `C:\php`
5. Copy `C:\php\php.ini-development` to `C:\php\php.ini`
6. Edit `php.ini` — uncomment these lines (remove the `;`):
   ```
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=zip
   extension=gd
   ```
7. Verify: Open new terminal → `php -v`

### Option B: Install via Laragon (easiest, all-in-one)
1. Download Laragon Full: https://laragon.org/download/
2. Install → it includes PHP, MySQL, Composer, Node.js
3. Open Laragon → Start All → Terminal has everything ready

### Option C: Install via XAMPP
1. Download XAMPP: https://www.apachefriends.org/
2. Install → PHP is at `C:\xampp\php\php.exe`
3. Add `C:\xampp\php` to PATH

---

## Step 2: Install Composer

1. Download: https://getcomposer.org/Composer-Setup.exe
2. Run installer → it auto-detects PHP path
3. Verify: Open new terminal → `composer --version`

---

## Step 3: Install MySQL

### Option A: Use XAMPP/Laragon MySQL (included)
### Option B: Install standalone MySQL:
1. Download MySQL Community: https://dev.mysql.com/downloads/mysql/
2. Install with default settings
3. Create database: `pga1_management`

---

## Step 4: Create Laravel Project

```bash
cd E:\PG A1 Laravel\backend
composer create-project laravel/laravel . --prefer-dist
```

If `backend/` already has our files, do this instead:
```bash
cd E:\PG A1 Laravel
composer create-project laravel/laravel backend-fresh --prefer-dist
```
Then merge our custom files (models, controllers, migrations, routes) into `backend-fresh/`.

---

## Step 5: Install Sanctum

```bash
cd backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## Step 6: Configure .env

Edit `backend/.env`:
```env
APP_NAME="PG A1 Management"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pga1_management
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:5173
```

---

## Step 7: Run Migrations & Seed

```bash
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=PgLocationSeeder
```

---

## Step 8: Start Server

```bash
php artisan serve
```
→ API available at http://localhost:8000/api/v1/

---

## Step 9: Test

```bash
# Public endpoint (no auth)
curl http://localhost:8000/api/v1/public/pg-locations

# Login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"mobile":"9999999999","password":"SuperAdmin@123"}'
```

---

## Quick Install (One-liner after PHP + Composer are ready)

```powershell
cd "E:\PG A1 Laravel\backend"
composer create-project laravel/laravel temp --prefer-dist
Move-Item temp/* . -Force
Remove-Item temp -Recurse -Force
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
# Edit .env with DB credentials
php artisan key:generate
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=PgLocationSeeder
php artisan serve
```
