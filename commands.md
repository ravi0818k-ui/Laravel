# PG A1 – Terminal Commands to Run the Project

## 1. Start MySQL (XAMPP)

```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini" -WindowStyle Hidden
```

Verify MySQL is running:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 'MySQL is running!' AS status;"
```

## 2. Start Laravel Server

```powershell
cd "e:\PG A1 Laravel\backend"
php artisan serve --port=8000
```

Or with custom PHP settings (for large file uploads):
```powershell
cd "e:\PG A1 Laravel\backend"
php -c php-server.ini artisan serve
```

## 3. Access the Application

| Page | URL |
|------|-----|
| Login | http://127.0.0.1:8000/dashboard/login.html |
| Super Admin | http://127.0.0.1:8000/dashboard/super-admin.html |
| Admin Panel | http://127.0.0.1:8000/dashboard/admin.html |
| Tenant Dashboard | http://127.0.0.1:8000/dashboard/tenant.html |

---

## First Time Setup (One-time only)

### Install dependencies
```powershell
cd "e:\PG A1 Laravel\backend"
composer install
```

### Create .env file
```powershell
cp .env.example .env
php artisan key:generate
```

### Create database
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE pga1_management;"
```

### Run migrations
```powershell
php artisan migrate
```

### Create Super Admin user
```powershell
php artisan tinker --execute="App\Models\User::create(['name'=>'Super Admin','mobile'=>'9999999999','password'=>Hash::make('admin123'),'role'=>'super_admin','is_active'=>true]);"
```

---

## Useful Commands

### Check database connection
```powershell
php artisan migrate:status
```

### Clear all caches
```powershell
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Stop MySQL (XAMPP)
```powershell
C:\xampp\mysql\bin\mysqladmin.exe -u root shutdown
```

### Push code to GitHub
```powershell
cd "e:\PG A1 Laravel"
git add -A
git commit -m "your message"
git push
```

---

## Quick Start (Run every time)

Open PowerShell and run these 2 commands:

```powershell
# 1. Start MySQL
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini" -WindowStyle Hidden

# 2. Start Laravel (keep this terminal open)
cd "e:\PG A1 Laravel\backend"
php artisan serve --port=8000
```

Then open: http://127.0.0.1:8000/dashboard/login.html

Login: `9999999999` / `admin123`
