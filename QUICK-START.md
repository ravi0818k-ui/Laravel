# PG A1 — Quick Start (Terminal Commands)

Run these commands in order to get the project running locally.

---

## Prerequisites

Install these first if you don't have them:

| Software | Download |
|----------|----------|
| XAMPP (PHP 8.2 + MySQL) | https://www.apachefriends.org/download.html |
| Composer | https://getcomposer.org/download/ |

After installing XAMPP, add `C:\xampp\php` and `C:\xampp\mysql\bin` to your system PATH.

Verify:
```cmd
php -v
composer --version
mysql --version
```

---

## Step 1: Start MySQL

Open **XAMPP Control Panel** → click **Start** next to MySQL.

---

## Step 2: Create Database

Open a terminal and run:

```cmd
mysql -u root -e "CREATE DATABASE pga1_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or open http://localhost/phpmyadmin → click "New" → create `pga1_management`.

---

## Step 3: Install Dependencies

```cmd
cd "E:\PG A1 Laravel\backend"
composer install
```

---

## Step 4: Configure Environment

```cmd
copy .env.example .env
```

Open `backend\.env` in any editor and set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pga1_management
DB_USERNAME=root
DB_PASSWORD=
```

> XAMPP default: username `root`, no password. If you set a MySQL password, put it here.

---

## Step 5: Generate App Key

```cmd
php artisan key:generate
```

---

## Step 6: Run Migrations (Create Tables)

```cmd
php artisan migrate
```

You'll see ~21 tables created:
```
2024_01_01_000001_create_users_table ........... DONE
2024_01_01_000002_create_pg_locations_table .... DONE
...
```

---

## Step 7: Seed Database (Create Super Admin + PG Locations)

```cmd
php artisan db:seed
```

This creates:
- Super Admin → mobile: `9999999999`, password: `SuperAdmin@123`
- 5 PG Locations

---

## Step 8: Copy Dashboard Files to Public

```cmd
xcopy dashboards\*.html public\dashboard\ /Y
xcopy dashboards\js\*.js public\dashboard\js\ /Y
```

---

## Step 9: Start the Server

```cmd
php artisan serve
```

Output:
```
INFO  Server running on [http://127.0.0.1:8000].
```

**Keep this terminal open.**

---

## Step 10: Open in Browser

| Page | URL |
|------|-----|
| PG Website | http://127.0.0.1:8000 |
| API Test | http://127.0.0.1:8000/api/v1/public/pg-locations |
| Login | http://127.0.0.1:8000/dashboard/login.html |

Login with:
- Mobile: `9999999999`
- Password: `SuperAdmin@123`

---

## All Commands in One Go (Copy-Paste)

```cmd
cd "E:\PG A1 Laravel\backend"
mysql -u root -e "CREATE DATABASE pga1_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
xcopy dashboards\*.html public\dashboard\ /Y
xcopy dashboards\js\*.js public\dashboard\js\ /Y
php artisan serve
```

Then edit `.env` → set `DB_DATABASE=pga1_management` before running migrate.

---

## Reset Everything (Fresh Start)

```cmd
php artisan migrate:fresh --seed
```

Drops all tables, recreates them, seeds Super Admin + PG data.

---

## Troubleshooting

| Error | Fix |
|-------|-----|
| `php is not recognized` | Add `C:\xampp\php` to PATH, open new terminal |
| `SQLSTATE Connection refused` | Start MySQL in XAMPP Control Panel |
| `Access denied for user 'root'` | Check DB_PASSWORD in `.env` |
| `Table already exists` | Run `php artisan migrate:fresh --seed` |
| Dashboard stuck on "Loading..." | Check server is running, open browser console (F12) for errors |
| `Class not found` | Run `composer dump-autoload` |
