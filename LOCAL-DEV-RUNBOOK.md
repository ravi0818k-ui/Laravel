# Local Dev Runbook (for AI agents / Claude Code)

Command-line-first guide to running PG A1 locally on this machine, using **XAMPP** (Apache/PHP/MariaDB) and the **MySQL CLI** — no phpMyAdmin, no GUI clicking. This machine already has XAMPP installed at `C:\xampp` and the project already has a working `backend/.env`, so most of this is "how to drive what's already set up," not first-time install.

For narrative/human-oriented setup docs see `DEVELOPER-SETUP-GUIDE.md`, `QUICK-START.md`, `backend/INSTALL.md` — those are GUI-flavored and slightly stale (see Discrepancies below). This file is the accurate, CLI-only reference.

## Environment facts (verified on this machine)

- PHP: `C:\xampp\php\php.exe` → `php -v` reports 8.3.32 (project requires `^8.2`, so fine)
- MySQL client/server: XAMPP's MariaDB 10.4.32 at `C:\xampp\mysql\bin`
- `php` and `mysql` are both already on PATH — no need to add them
- `backend/.env` already exists and is configured:
  - `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=pga1_management`, `DB_USERNAME=root`, `DB_PASSWORD=` (empty — XAMPP default)
  - `APP_URL=http://localhost:8000`
- Dashboards are already copied into `backend/public/dashboard/` (`login.html`, `admin.html`, `super-admin.html`, `tenant.html`, `js/`)
- `backend/public/storage` is already a working symlink to `backend/storage/app/public`

So on this machine, day-to-day is just: **start MySQL → `php artisan serve`**. Full install steps are included below for a fresh clone/machine.

## Starting services from the command line (no XAMPP Control Panel GUI)

XAMPP ships `.bat` files that start/stop each service headlessly:

```bash
# Start MySQL (MariaDB)
"C:\xampp\mysql_start.bat"

# Start Apache (not required for this project — php artisan serve is used instead,
# but XAMPP's Apache/phpMyAdmin needs it if you want phpMyAdmin)
"C:\xampp\apache_start.bat"

# Stop
"C:\xampp\mysql_stop.bat"
"C:\xampp\apache_stop.bat"
```

Verify MySQL is actually up:

```bash
"C:\xampp\mysql\bin\mysqladmin.exe" -u root ping
# expect: mysqld is alive
```

## Working with the database via `mysql` CLI

```bash
# Open an interactive shell
mysql -u root

# One-off queries without an interactive shell
mysql -u root -e "SHOW DATABASES;"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS pga1_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root pga1_management -e "SHOW TABLES;"
mysql -u root pga1_management -e "SELECT id, mobile, role FROM users;"
mysql -u root pga1_management -e "SELECT COUNT(*) FROM tenants WHERE deleted_at IS NULL;"

# Dump / restore (for backups or moving data between machines)
mysqldump -u root pga1_management > pga1_management_backup.sql
mysql -u root pga1_management < pga1_management_backup.sql
```

No password flag is needed (`DB_PASSWORD=` is empty for root in this env). If a password were ever set, every command above needs `-p` (interactive prompt) or `-pYOURPASS` (no space after `-p`).

## Running the app

From `backend/`:

```bash
cd "E:\PG A1 Laravel\backend"

# normal run
php artisan serve
# App + API both served at http://127.0.0.1:8000

# when testing uploads (payment screenshots, meter images, onboarding documents) —
# `php -c php-server.ini artisan serve` LOOKS right but silently does nothing; see below.
$env:PHP_INI_SCAN_DIR = "$PWD\php-ini-scan"; php artisan serve --no-reload   # PowerShell
PHP_INI_SCAN_DIR="$(pwd)/php-ini-scan" php artisan serve --no-reload         # bash
```

Keep that process running in its own terminal/background job; open a second terminal for everything else (migrations, `mysql` queries, etc.).

**Why the obvious `-c php-server.ini` form doesn't work:** `artisan serve` spawns a *separate* `php -S host:port server.php` child process to actually handle requests (see `ServeCommand::serverCommand()` in `vendor/laravel/framework`), launched with no `-c`/`-d` flags at all — so an ini file passed to the outer `artisan serve` command never reaches the process that parses uploads. On top of that, whenever a `.env` file exists and `--no-reload` isn't passed, Laravel strips almost every environment variable (including `TEMP`/`TMP`) from that child. On Windows, `sys_get_temp_dir()` needs those, so without them PHP falls back to `C:\Windows` (unwritable) and **every** upload fails with "unable to create a temporary file" — which shows up as a 422 that looks like a validation error but has nothing to do with the submitted data. `--no-reload` stops the stripping; `PHP_INI_SCAN_DIR` is an actual environment variable PHP reads on its own at startup, so (unlike `-c`) it survives being passed through to the child process. Tradeoff: `--no-reload` also disables `artisan serve`'s auto-restart when `.env` changes — restart manually after editing it.

Full multi-process dev mode (server + queue listener + logs + Vite), if you need the Vite/Tailwind pipeline:

```bash
composer run dev
```

## Common CLI workflows

```bash
# Fresh clone / first-time setup on a new machine
cd "E:\PG A1 Laravel\backend"
composer install
copy .env.example .env
php artisan key:generate
# then edit .env: DB_DATABASE=pga1_management, DB_USERNAME=root, DB_PASSWORD=
mysql -u root -e "CREATE DATABASE pga1_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed

# Reset DB to a clean seeded state (dev only — drops everything)
php artisan migrate:fresh --seed

# Route/config cache acting stale
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Autoload issues ("Class not found")
composer dump-autoload

# Standalone integration script (needs real seeded data already in the DB, not a fresh empty one)
php tests/flow_test.php

# PHPUnit
php artisan test
```

## Login credentials (seeded)

- Super Admin — mobile `9999999999`, password `SuperAdmin@123` (from `backend/database/seeders/SuperAdminSeeder.php`)

## Quick smoke test

```bash
curl http://127.0.0.1:8000/api/v1/public/pg-locations

curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d "{\"mobile\":\"9999999999\",\"password\":\"SuperAdmin@123\"}"
```

Dashboards in the browser: `http://127.0.0.1:8000/dashboard/login.html`.

## Discrepancies vs other docs (worth knowing before trusting them)

- Root `CLAUDE.md` states the super admin password as `admin123`. The actual seeder (`SuperAdminSeeder.php`) sets `SuperAdmin@123`. Trust the seeder / this file.
- `DEVELOPER-SETUP-GUIDE.md`, `QUICK-START.md`, and `backend/INSTALL.md` assume XAMPP PHP 8.2.12 exactly; this machine actually runs PHP 8.3.32 via XAMPP, which still satisfies the project's `^8.2` constraint — not a problem, just not literally what those docs say.
