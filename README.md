# PG A1 – Paying Guest Management System

A full-stack PG (Paying Guest) accommodation management system built with **Laravel 11** (API backend) and **vanilla HTML/JS/Tailwind CSS** (frontend dashboards).

## Features

### Roles
- **Super Admin** – Full system access: manage PG locations, admins, global stats
- **Admin** – Scoped to assigned PGs: manage tenants, rooms, payments, onboarding, electricity, expenses, notes
- **Tenant** – Self-service: view rent, upload payments, see electricity bills, profile

### Key Modules
- Tenant onboarding (bulk/single link, document upload, auto-login after submission)
- Room & bed management (add, delete, allocate)
- Monthly rent generation & payment verification (screenshot upload)
- Electricity billing (room-wise, auto-split among tenants, meter images)
- Expense tracking with bill images
- Notes section
- Tenant trash (soft delete / restore / permanent delete)
- Admin impersonation
- Excel export for electricity bills
- First payment tracking (rent + security deposit individually or combined)

---

## Prerequisites

Install the following software:

| Software | Version | Download |
|----------|---------|----------|
| PHP | 8.2+ | https://windows.php.net/download/ |
| Composer | Latest | https://getcomposer.org/download/ |
| MySQL | 8.0+ | https://dev.mysql.com/downloads/mysql/ |
| Node.js (optional) | 18+ | https://nodejs.org/ |
| Git | Latest | https://git-scm.com/downloads |

### PHP Extensions Required
Make sure these are enabled in `php.ini`:
```
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=fileinfo
extension=gd
```

### PHP Upload Limits (Important)
Update your `php.ini` for file uploads:
```ini
upload_max_filesize = 50M
post_max_size = 100M
max_execution_time = 300
memory_limit = 512M
```

---

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/ravi0818k-ui/Laravel.git
cd Laravel
```

### 2. Install PHP dependencies
```bash
cd backend
composer install
```

### 3. Setup environment file
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database
Edit `backend/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pga1_management
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Create the database
Open MySQL and run:
```sql
CREATE DATABASE pga1_management;
```

### 6. Run migrations
```bash
php artisan migrate
```

### 7. Create Super Admin user
```bash
php artisan tinker
```
Then paste:
```php
App\Models\User::create([
    'name' => 'Super Admin',
    'mobile' => '9999999999',
    'password' => Hash::make('admin123'),
    'role' => 'super_admin',
    'is_active' => true,
]);
```
Type `exit` to close tinker.

### 8. Start the server
```bash
php artisan serve
```
Or with custom PHP settings for file uploads:
```bash
php -c php-server.ini artisan serve
```

The API will be available at: `http://127.0.0.1:8000`

---

## Accessing the Dashboards

Open these URLs in your browser:

| Dashboard | URL |
|-----------|-----|
| Login | http://127.0.0.1:8000/dashboard/login.html |
| Admin Panel | http://127.0.0.1:8000/dashboard/admin.html |
| Super Admin | http://127.0.0.1:8000/dashboard/super-admin.html |
| Tenant Dashboard | http://127.0.0.1:8000/dashboard/tenant.html |
| Public Landing Page | Open `index.html` directly or via Live Server |
| Onboarding Form | Generated via admin panel (unique token links) |

---

## Default Login Credentials

| Role | Mobile | Password |
|------|--------|----------|
| Super Admin | `9999999999` | `admin123` |
| Admin | (created by Super Admin) | (set during creation) |
| Tenant | (mobile number) | (set during onboarding, or mobile number as default) |

---

## Project Structure

```
Laravel/
├── index.html                          # Public marketing/landing page
├── onboarding.html                     # Public onboarding form (old version)
├── images/                             # Landing page images
├── backend/
│   ├── .env                            # Environment configuration
│   ├── artisan                         # Laravel CLI
│   ├── composer.json                   # PHP dependencies
│   ├── php-server.ini                  # Custom PHP config for uploads
│   ├── app/
│   │   ├── Http/Controllers/Api/       # API controllers
│   │   ├── Models/                     # Eloquent models
│   │   ├── Services/                   # Business logic services
│   │   └── Http/Middleware/            # Auth & role middleware
│   ├── config/                         # Laravel config (cors, auth, sanctum)
│   ├── database/migrations/            # Database schema
│   ├── routes/api.php                  # API routes
│   ├── public/
│   │   ├── dashboard/                  # Frontend HTML dashboards (served by Laravel)
│   │   │   ├── login.html
│   │   │   ├── admin.html
│   │   │   ├── super-admin.html
│   │   │   ├── tenant.html
│   │   │   └── js/
│   │   │       ├── api.js              # Central API client
│   │   │       └── modal.js            # Modal/toast utilities
│   │   └── onboarding.html            # Tenant onboarding form
│   └── dashboards/                     # Source copies of dashboard files
```

---

## API Base URL

All API endpoints are under:
```
http://127.0.0.1:8000/api/v1/
```

Authentication uses **Laravel Sanctum** (Bearer token). Login returns a token that must be sent in the `Authorization` header for all authenticated requests.

---

## Key API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/login` | Login (returns token) |
| POST | `/api/v1/logout` | Logout |
| GET | `/api/v1/me` | Get current user |
| GET | `/api/v1/admin/tenants` | List tenants |
| GET | `/api/v1/admin/rooms` | List rooms with beds |
| POST | `/api/v1/admin/beds` | Create a bed |
| POST | `/api/v1/admin/onboarding/invite` | Generate onboarding link |
| POST | `/api/v1/admin/rents/generate` | Generate monthly rent |
| GET | `/api/v1/admin/payments` | List pending payments |
| POST | `/api/v1/admin/electricity-bills` | Create electricity bill |
| GET | `/api/v1/tenant/dashboard` | Tenant dashboard data |
| POST | `/api/v1/tenant/payments` | Submit payment screenshot |

---

## Troubleshooting

### "413 Content Too Large" error on file uploads
Update `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 100M
```
Then restart the server.

### CORS issues
The `config/cors.php` allows `localhost` origins. For production, add your domain:
```php
'allowed_origins' => [
    'https://yourdomain.com',
],
```

### Database connection refused
- Ensure MySQL is running
- Check `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` in `.env`
- Run `php artisan config:clear`

---

## Production Deployment

For deploying to a server (e.g., ServerByt), see `SERVERBYT-DEPLOYMENT-GUIDE.md` in the root directory.

Key steps:
1. Update `API_BASE` in `public/dashboard/js/api.js` to your production URL
2. Update `config/cors.php` with your production domain
3. Set `APP_DEBUG=false` and `APP_ENV=production` in `.env`
4. Run `php artisan config:cache` and `php artisan route:cache`

---

## License

Private project. All rights reserved.
