# ============================================================
# PG A1 Management System — Automated Setup Script (Windows)
# ============================================================
# Run this AFTER installing PHP and Composer.
# Usage: Open PowerShell → cd "E:\PG A1 Laravel\backend" → .\setup.ps1
# ============================================================

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  PG A1 Management System — Setup" -ForegroundColor Cyan  
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check prerequisites
Write-Host "[1/9] Checking prerequisites..." -ForegroundColor Yellow
try {
    $phpVersion = php -v 2>&1 | Select-Object -First 1
    Write-Host "  ✓ PHP found: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "  ✗ PHP not found! Install PHP first (see INSTALL.md)" -ForegroundColor Red
    exit 1
}

try {
    $composerVersion = composer --version 2>&1 | Select-Object -First 1
    Write-Host "  ✓ Composer found: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Composer not found! Install Composer first (see INSTALL.md)" -ForegroundColor Red
    exit 1
}

# Create Laravel project in a temp directory
Write-Host ""
Write-Host "[2/9] Creating Laravel project..." -ForegroundColor Yellow
$backendDir = $PSScriptRoot
$tempDir = Join-Path $backendDir "laravel-temp"

if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }

composer create-project laravel/laravel $tempDir --prefer-dist --no-interaction
if ($LASTEXITCODE -ne 0) { Write-Host "  ✗ Laravel creation failed!" -ForegroundColor Red; exit 1 }
Write-Host "  ✓ Laravel project created" -ForegroundColor Green

# Move Laravel files to backend root (without overwriting our custom files)
Write-Host ""
Write-Host "[3/9] Merging Laravel skeleton with custom code..." -ForegroundColor Yellow

# Copy Laravel structure files that we don't have custom versions of
$laravelFiles = @(
    "artisan", "composer.json", "composer.lock", "package.json", 
    "phpunit.xml", "vite.config.js", ".env.example", ".gitignore",
    "bootstrap", "config", "public", "resources", "storage", "tests", "vendor"
)

foreach ($item in $laravelFiles) {
    $source = Join-Path $tempDir $item
    $dest = Join-Path $backendDir $item
    if (Test-Path $source) {
        if (-not (Test-Path $dest)) {
            if ((Get-Item $source).PSIsContainer) {
                Copy-Item $source $dest -Recurse -Force
            } else {
                Copy-Item $source $dest -Force
            }
        }
    }
}

# Always copy vendor (dependencies)
$vendorSource = Join-Path $tempDir "vendor"
$vendorDest = Join-Path $backendDir "vendor"
if (Test-Path $vendorDest) { Remove-Item $vendorDest -Recurse -Force }
Copy-Item $vendorSource $vendorDest -Recurse -Force

# Copy Laravel's bootstrap dir
$bootstrapSource = Join-Path $tempDir "bootstrap"
$bootstrapDest = Join-Path $backendDir "bootstrap"
if (Test-Path $bootstrapDest) { Remove-Item $bootstrapDest -Recurse -Force }
Copy-Item $bootstrapSource $bootstrapDest -Recurse -Force

# Copy config
$configSource = Join-Path $tempDir "config"
$configDest = Join-Path $backendDir "config"
if (Test-Path $configDest) { Remove-Item $configDest -Recurse -Force }
Copy-Item $configSource $configDest -Recurse -Force

# Copy storage
$storageSource = Join-Path $tempDir "storage"
$storageDest = Join-Path $backendDir "storage"
if (-not (Test-Path $storageDest)) { Copy-Item $storageSource $storageDest -Recurse -Force }

# Copy public
$publicSource = Join-Path $tempDir "public"
$publicDest = Join-Path $backendDir "public"
if (-not (Test-Path $publicDest)) { Copy-Item $publicSource $publicDest -Recurse -Force }

# Copy essential root files
Copy-Item (Join-Path $tempDir "artisan") (Join-Path $backendDir "artisan") -Force
Copy-Item (Join-Path $tempDir "composer.json") (Join-Path $backendDir "composer.json") -Force
Copy-Item (Join-Path $tempDir "composer.lock") (Join-Path $backendDir "composer.lock") -Force

Write-Host "  ✓ Files merged" -ForegroundColor Green

# Clean up temp
Remove-Item $tempDir -Recurse -Force
Write-Host "  ✓ Temp directory cleaned" -ForegroundColor Green

# Install Sanctum
Write-Host ""
Write-Host "[4/9] Installing Laravel Sanctum..." -ForegroundColor Yellow
Set-Location $backendDir
composer require laravel/sanctum --no-interaction
if ($LASTEXITCODE -ne 0) { Write-Host "  ✗ Sanctum install failed!" -ForegroundColor Red; exit 1 }
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
Write-Host "  ✓ Sanctum installed" -ForegroundColor Green

# Create .env
Write-Host ""
Write-Host "[5/9] Creating .env configuration..." -ForegroundColor Yellow
$envContent = @"
APP_NAME="PG A1 Management"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pga1_management
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:5173,127.0.0.1:8000
"@
$envContent | Out-File -FilePath (Join-Path $backendDir ".env") -Encoding UTF8
Write-Host "  ✓ .env created" -ForegroundColor Green

# Generate app key
Write-Host ""
Write-Host "[6/9] Generating application key..." -ForegroundColor Yellow
php artisan key:generate
Write-Host "  ✓ App key generated" -ForegroundColor Green

# Create storage link
Write-Host ""
Write-Host "[7/9] Creating storage symlink..." -ForegroundColor Yellow
php artisan storage:link 2>$null
Write-Host "  ✓ Storage linked" -ForegroundColor Green

# Run migrations
Write-Host ""
Write-Host "[8/9] Running database migrations..." -ForegroundColor Yellow
Write-Host "  NOTE: Make sure MySQL is running and database 'pga1_management' exists!" -ForegroundColor Magenta
Write-Host "  Create it with: CREATE DATABASE pga1_management;" -ForegroundColor Magenta
Write-Host ""

$runMigrations = Read-Host "  Run migrations now? (y/n)"
if ($runMigrations -eq "y") {
    php artisan migrate
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  ✗ Migration failed! Check your .env DB settings." -ForegroundColor Red
    } else {
        Write-Host "  ✓ Migrations complete" -ForegroundColor Green
        
        # Seed
        Write-Host ""
        Write-Host "[9/9] Seeding database..." -ForegroundColor Yellow
        php artisan db:seed --class=Database\Seeders\SuperAdminSeeder
        php artisan db:seed --class=Database\Seeders\PgLocationSeeder
        Write-Host "  ✓ Database seeded" -ForegroundColor Green
    }
} else {
    Write-Host "  Skipped. Run manually: php artisan migrate" -ForegroundColor Yellow
}

# Done
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ✓ Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Start the server:" -ForegroundColor Cyan
Write-Host "    cd $backendDir" -ForegroundColor White
Write-Host "    php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "  API: http://localhost:8000/api/v1/" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Super Admin Login:" -ForegroundColor Cyan
Write-Host "    Mobile: 9999999999" -ForegroundColor White
Write-Host "    Password: SuperAdmin@123" -ForegroundColor White
Write-Host ""
