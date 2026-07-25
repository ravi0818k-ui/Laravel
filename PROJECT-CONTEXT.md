# PG A1 — Project Context for AI Assistants

This document provides full context for any AI assistant (Kiro, Claude, etc.) to understand this project quickly.

---

## What Is This Project?

**PG A1** is a complete **PG (Paying Guest) management system** for a real business in Gurugram, India that operates 5 PG properties and 1 BHK rental. It has:

1. **Public website** — Property listings, photos, contact info (static HTML)
2. **Laravel API backend** — REST API for all business logic
3. **Admin dashboard** — Manage tenants, rooms, rent, payments, electricity, onboarding
4. **Tenant dashboard** — View rent, upload payment proofs, see electricity bills
5. **Onboarding form** — Public form for new tenant registration with Aadhaar PDF decryption

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Database | MySQL |
| Auth | Laravel Sanctum (token-based) |
| Frontend | Static HTML + Tailwind CSS (via CDN) + vanilla JavaScript |
| PDF Decryption | pdf.js (client-side, for encrypted Aadhaar PDFs) |
| Hosting Target | ServerByt shared hosting (no SSH, cPanel-style) |

**No frontend build step.** All dashboards are plain HTML files that call the API via fetch(). No React, Vue, or any JS framework.

---

## Folder Structure

```
E:\PG A1 Laravel\
├── backend/                         ← Laravel application
│   ├── app/
│   │   ├── Http/Controllers/Api/    ← All API controllers
│   │   ├── Models/                  ← Eloquent models
│   │   ├── Services/                ← Business logic services
│   │   └── Http/Middleware/         ← Auth & role middleware
│   ├── bootstrap/
│   ├── config/
│   ├── database/migrations/         ← All table schemas
│   ├── dashboards/                  ← Source HTML dashboards (dev copies)
│   │   ├── admin.html
│   │   ├── tenant.html
│   │   ├── login.html
│   │   ├── super-admin.html
│   │   └── js/api.js, modal.js
│   ├── public/                      ← Served files (copied from dashboards + website)
│   │   ├── index.html              ← Public PG website
│   │   ├── index.php               ← Laravel entry point
│   │   ├── onboarding.html         ← Tenant onboarding form
│   │   ├── dashboard/              ← Deployed dashboard copies
│   │   └── images/, PG photo folders
│   ├── routes/api.php               ← All API routes
│   ├── storage/
│   ├── vendor/
│   └── .env
│
├── images/                          ← PG property photos (also in backend/public/)
├── PGA1 jharsa village-done/        ← Property photo folders
├── PGA1 Sarswati Vihar PG-done/
├── PGA1 Sec 46-done/
├── PGA1 1BHK saraswati vihar-done/
├── index.html                       ← Public PG website (root copy)
├── styles.css
├── pg-data.json                     ← PG property data for the website
├── DEVELOPER-SETUP-GUIDE.md         ← Local dev setup instructions
├── SERVERBYT-DEPLOYMENT-GUIDE.md    ← Production deployment guide
└── PROJECT-CONTEXT.md               ← This file
```

### Important: Two Copies of Dashboard Files

- `backend/dashboards/` → **Source of truth** (edit these)
- `backend/public/dashboard/` → **Deployed copies** (must be synced after edits)

After editing any dashboard file, always copy to `backend/public/dashboard/`:
```powershell
Copy-Item "backend\dashboards\admin.html" "backend\public\dashboard\admin.html" -Force
Copy-Item "backend\dashboards\tenant.html" "backend\public\dashboard\tenant.html" -Force
Copy-Item "backend\dashboards\js\api.js" "backend\public\dashboard\js\api.js" -Force
```

---

## User Roles & Access

| Role | Dashboard | What they do |
|------|-----------|-------------|
| `super_admin` | super-admin.html | Create admins, create PG locations, assign PGs to admins |
| `admin` | admin.html | Manage tenants, rooms, beds, rent, payments, electricity, onboarding |
| `tenant` | tenant.html | View rent, upload payment proof, view electricity bills, see profile |

---

## Key Business Flows

### 1. Tenant Onboarding
- Admin generates a **mass onboarding link** (one link, multiple candidates can use it)
- Candidate fills form: personal info, references, password, documents
- **Aadhaar must be PDF** → decrypted client-side with pdf.js → rendered to JPEG → sent as image
- Voter ID is optional, image only
- Admin reviews applications → approves → system creates user account, assigns room/bed
- Documents are auto-marked as "verified" on approval

### 2. Monthly Rent
- Admin clicks **"Generate Rent"** for a month → creates unpaid rent records for all active tenants
- Tenant sees rent card with amount + electricity = total
- Tenant uploads payment screenshot
- Status flow: **Unpaid** → **In Progress** (uploaded, pending verification) → **Paid** (admin verified)
- If admin rejects, tenant can re-upload

### 3. Electricity Billing
- Admin creates bill for a room: previous/current meter readings + images + rate per unit
- System auto-splits total cost among active tenants in that room
- Tenant sees their share with meter photos viewable
- Admin can mark individual tenant's electricity as paid separately from rent
- Admin can adjust electricity per tenant individually

### 4. Payment Verification
- Admin sees all pending payments with **screenshot preview**
- Admin can view full-size screenshot (loaded via auth headers)
- Admin verifies (confirms amount) or rejects (with reason)
- Verification updates rent status to "paid"

---

## API Structure

Base: `/api/v1/`

### Public (no auth)
- `GET /public/pg-locations` — list PG properties
- `GET /onboarding/{token}/validate` — check if onboarding link is valid
- `POST /onboarding/{token}/submit` — submit onboarding form

### Auth
- `POST /login` — returns Sanctum token
- `POST /logout`

### Tenant (role: tenant)
- `GET /tenant/dashboard` — home data (current rent + electricity + roommates)
- `GET /tenant/profile` — full profile + documents
- `GET /tenant/rents` — rent history with payment submissions
- `GET /tenant/electricity` — electricity allocations
- `GET /tenant/electricity/{bill}/meter-image/{type}` — view meter photo
- `POST /tenant/payments` — upload payment screenshot

### Admin (role: admin, super_admin)
- `GET /admin/tenants` — list with latest rent payment status
- `GET /admin/tenants/{id}` — full detail with all relationships
- `POST /admin/tenants/{id}/change-rent`
- `POST /admin/tenants/{id}/change-bed`
- `POST /admin/tenants/{id}/reset-password`
- `POST /admin/tenants/{id}/impersonate` — get temp token to view tenant's dashboard
- `POST /admin/tenants/{id}/electricity-adjustment`
- `GET /admin/payments` — pending payment submissions
- `GET /admin/payments/{id}/screenshot` — serve screenshot image
- `POST /admin/payments/{id}/verify`
- `POST /admin/payments/{id}/reject`
- `GET /admin/rooms`, `POST /admin/rooms`
- `GET /admin/beds`, `POST /admin/beds`, `PUT /admin/beds/{id}`
- `POST /admin/onboarding/invite` — generate mass onboarding link
- `GET /admin/onboarding/applications`
- `POST /admin/onboarding/{id}/approve`
- `POST /admin/rents/generate` — generate monthly rent for all tenants
- `POST /admin/electricity-bills` — create bill with meter images
- `GET /admin/electricity-bills`
- `GET /admin/electricity-bills/{id}/meter-image/{type}`
- `POST /admin/electricity-allocations/{id}/mark-paid`

### Super Admin
- `GET /super-admin/dashboard`
- CRUD for admins and PG locations

---

## Database Models & Relationships

```
User (id, name, mobile, password, role)
  └── Tenant (user_id, tenant_id, pg_location_id, current_rent, joining_date, ...)
        ├── TenantBedAllocation (tenant_id, bed_id, is_current)
        ├── MonthlyRent (tenant_id, billing_month, total_amount, status)
        │     └── PaymentSubmission (monthly_rent_id, screenshot_path, status, verified_amount)
        ├── ElectricityBillAllocation (tenant_id, electricity_bill_id, amount, status)
        ├── TenantDocument (tenant_id, document_type, file_path, verification_status)
        └── TenantRentHistory (tenant_id, previous_rent, new_rent)

PgLocation (id, name, city)
  └── Room (pg_location_id, room_number, floor, room_type)
        └── Bed (room_id, bed_number, monthly_rent, status)

ElectricityBill (room_id, billing_month, total_units, rate_per_unit, previous/current_meter_image)
  └── ElectricityBillAllocation (electricity_bill_id, tenant_id, amount, status)

OnboardingInvitation (token, pg_location_id, status, candidate_*, expires_at)
  └── TenantDocument (onboarding_invitation_id, ...)

AdminPgAssignment (user_id, pg_location_id) — which admin manages which PG
```

---

## Key Technical Decisions

1. **No frontend framework** — Plain HTML + JS + Tailwind CDN. Fast to load, easy to deploy on shared hosting.
2. **Aadhaar decryption client-side** — pdf.js handles AES-256 encrypted PDFs in browser → renders to canvas → converts to JPEG → sends image to backend. Server never receives encrypted PDF.
3. **Mass onboarding** — One link usable by many people. Each submission creates a separate application record.
4. **File storage** — All uploads stored in Laravel's `storage/app/` (local disk). Served via authenticated controller endpoints (not public URLs).
5. **Payment screenshots** — Served via auth-protected endpoint using fetch + blob URLs (can't use `<img src>` with Bearer tokens).
6. **Impersonation** — Admin gets a 1-hour Sanctum token for the tenant, opens tenant dashboard in new tab.
7. **Mobile-first design** — Bottom nav on mobile, sidebar on desktop for admin. All dashboards responsive.

---

## How to Run Locally

```bash
cd backend
composer install
cp .env.example .env
# Edit .env with your local MySQL credentials
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Then open:
- Website: http://127.0.0.1:8000
- Dashboard: http://127.0.0.1:8000/dashboard/login.html
- API: http://127.0.0.1:8000/api/v1/public/pg-locations

Default Super Admin: mobile `9999999999`, password `SuperAdmin@123`

---

## Common Patterns When Editing

### Adding a new feature to Admin dashboard:
1. Edit `backend/dashboards/admin.html`
2. Add API method to `backend/dashboards/js/api.js`
3. Add route in `backend/routes/api.php`
4. Add controller method
5. Copy dashboard files to `backend/public/dashboard/`

### Adding a new field to a model:
1. Create migration: `php artisan make:migration add_field_to_table`
2. Update model's `$fillable` and `$casts`
3. Update relevant controller/service
4. Update frontend display

### Mobile number validation:
- Frontend: `oninput="this.value=this.value.replace(/[^0-9]/g,'')"` + `maxlength="10"` + regex `/^\d{10}$/`
- Backend: `'field' => 'required|string|digits:10'`

### Date formatting in frontend:
```javascript
function fmtDate(d) {
  if (!d) return '—';
  const date = new Date(d);
  if (isNaN(date)) return d;
  return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}
```

---

## Files That Should Stay in Sync

| Source | Copy (must match) |
|--------|-------------------|
| `backend/dashboards/admin.html` | `backend/public/dashboard/admin.html` |
| `backend/dashboards/tenant.html` | `backend/public/dashboard/tenant.html` |
| `backend/dashboards/login.html` | `backend/public/dashboard/login.html` |
| `backend/dashboards/super-admin.html` | `backend/public/dashboard/super-admin.html` |
| `backend/dashboards/js/api.js` | `backend/public/dashboard/js/api.js` |
| `backend/dashboards/js/modal.js` | `backend/public/dashboard/js/modal.js` |

---

## What NOT to Do

- Don't use `localhost` for DB_HOST in production (use panel's actual DB host)
- Don't put `vendor/` or `.env` in `public_html/`
- Don't forget to copy dashboard files after editing
- Don't accept image uploads for Aadhaar (PDF only, decrypted client-side)
- Don't show raw ISO dates — always use `fmtDate()` helper
- Don't use `<img src>` for auth-protected images — use fetch + blob URLs
