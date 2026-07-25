# PG A1 Management System — Development Task List

## Phase 1: Foundation (Auth + Roles + Setup) ✅ SCAFFOLDED
- [x] MySQL schema design (20 tables)
- [x] Laravel migrations (all tables)
- [x] Eloquent models with relationships
- [x] Sanctum auth (login/logout/me)
- [x] Role-based middleware (`EnsureRole`)
- [x] Admin PG access scoping middleware (`EnsureAdminPgAccess`)
- [x] Super Admin seeder
- [ ] **TODO:** Install Laravel project (`composer create-project laravel/laravel .`)
- [ ] **TODO:** Configure `.env` (DB credentials, app key)
- [ ] **TODO:** Run `php artisan migrate`
- [ ] **TODO:** Run `php artisan db:seed --class=SuperAdminSeeder`
- [ ] **TODO:** Install Sanctum (`composer require laravel/sanctum`, publish config)
- [ ] **TODO:** Register middleware aliases in Kernel

## Phase 2: PG → Room → Bed Management ✅ SCAFFOLDED
- [x] PgLocation CRUD (Super Admin create/update, Admin read)
- [x] Room CRUD
- [x] Bed CRUD with status management
- [x] RoomAllocationService (allocate/vacate bed)
- [ ] **TODO:** Form Request validation classes for each endpoint
- [ ] **TODO:** API Resource transformers (consistent JSON responses)

## Phase 3: Onboarding Flow ✅ SCAFFOLDED
- [x] Generate secure invitation token (Admin)
- [x] Validate token (public)
- [x] Submit onboarding form + document upload (public)
- [x] Approve application → create tenant + user + assign bed (Admin)
- [x] TenantIdService (sequential ID generation with DB locking)
- [ ] **TODO:** Reject application endpoint
- [ ] **TODO:** Document download endpoint (authenticated, permission-checked)
- [ ] **TODO:** Email/SMS notification on approval (optional)

## Phase 4: Admin + Super Admin Dashboards ✅ SCAFFOLDED
- [x] Super Admin dashboard stats
- [x] Admin management (create, assign PGs)
- [x] Tenant listing (scoped to admin's PGs)
- [x] Tenant detail view
- [ ] **TODO:** Admin dashboard endpoint (stats scoped to their PGs)
- [ ] **TODO:** Impersonation ("view as admin") with audit logging

## Phase 5: Monthly Rent + Payment Verification ✅ SCAFFOLDED
- [x] Rent generation (bulk for all active tenants)
- [x] Tenant rent listing
- [x] Payment submission with screenshot
- [x] Admin payment listing (pending verification)
- [x] Verify / Reject payment (with verified_amount)
- [x] Ledger recalculation on verified payment
- [x] Rent change with history
- [ ] **TODO:** Partial payment handling edge cases
- [ ] **TODO:** Overdue rent notifications

## Phase 6: Electricity Billing ✅ SCAFFOLDED
- [x] ElectricityBillingService (create bill + split among active tenants)
- [ ] **TODO:** Electricity bill controller endpoints
- [ ] **TODO:** Tenant view of electricity bills
- [ ] **TODO:** Mark electricity allocation as paid

## Phase 7: Document Verification
- [ ] Document listing per tenant (admin view)
- [ ] Verify / request correction with reason
- [ ] Tenant re-upload after correction request
- [ ] Secure download endpoint (private storage, auth-checked)

## Phase 8: Referral System
- [ ] Referral code already generated on tenant creation ✅
- [ ] Tenant view: list of people referred (masked mobiles)
- [ ] Admin: referral report

## Phase 9: Concerns / Tickets
- [ ] Tenant submit concern (with category + optional image)
- [ ] Admin list concerns (scoped to PGs)
- [ ] Update status (in_progress, resolved, closed)
- [ ] Super Admin: view all concerns

## Phase 10: Offboarding ✅ SERVICE SCAFFOLDED
- [x] OffboardingService (vacate bed, mark offboarded, deactivate user)
- [ ] **TODO:** Offboarding request endpoint (tenant initiates)
- [ ] **TODO:** Admin review + complete offboarding
- [ ] **TODO:** Outstanding dues check before completion

## Phase 11: Connect Static Website
- [ ] Public API (`/api/v1/public/pg-locations`) returns live availability ✅
- [ ] Update existing static site JS to fetch from API
- [ ] CORS configuration for the static site domain

## Phase 12: Reports + Activity Logs
- [x] ActivityLogService
- [ ] **TODO:** Integrate audit logging into all sensitive actions
- [ ] **TODO:** Activity log viewer (Super Admin)
- [ ] **TODO:** Rent collection report
- [ ] **TODO:** Occupancy report
- [ ] **TODO:** Revenue report by PG location

## Phase 13: Future — React Native / Android App
- [ ] Same API, mobile-optimized responses
- [ ] Push notifications (Firebase)
- [ ] Offline-first considerations

---

## Setup Instructions

1. Install PHP 8.2+ and Composer
2. Navigate to `backend/` directory
3. Run: `composer create-project laravel/laravel . --prefer-dist`
4. Copy generated files into the Laravel project structure
5. Configure `.env` with your MySQL credentials
6. Run: `composer require laravel/sanctum`
7. Run: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
8. Run: `php artisan migrate`
9. Run: `php artisan db:seed --class=SuperAdminSeeder`
10. Run: `php artisan serve`

Default Super Admin credentials:
- Mobile: `9999999999`
- Password: `SuperAdmin@123`
