# PG A1 Management System — Interview Questions

Comprehensive question bank covering all concepts used in this project.  
Organized: Basic → Intermediate → Advanced → Deep Dive

---

## 1. Laravel Fundamentals

### Basic

**Q1:** What is Laravel and why did you choose it for this project?  
**A:** Laravel is a PHP MVC framework. Chose it for built-in auth (Sanctum), Eloquent ORM, migration system, middleware pipeline, and rapid API development.

**Q2:** Explain the Laravel request lifecycle.  
**A:** Request → `public/index.php` → Service Container bootstrap → HTTP Kernel → Middleware → Router → Controller → Response sent back through middleware.

**Q3:** What is the difference between `web.php` and `api.php` routes?  
**A:** `web.php` has session/CSRF middleware (stateful). `api.php` is stateless with `api` prefix, rate limiting, and token-based auth. In our project, `web.php` serves onboarding HTML pages, `api.php` handles all REST endpoints.

**Q4:** What is Artisan? Name 5 commands you used.  
**A:** Laravel's CLI tool. Used: `php artisan serve`, `migrate`, `make:controller`, `make:model`, `storage:link`, `make:middleware`.

**Q5:** What is the `.env` file? How does Laravel use it?  
**A:** Environment configuration file. Laravel uses `vlucas/phpdotenv` to load key-value pairs. Accessed via `env()` helper or `config()`. Never committed to version control. Different values for local vs production.

**Q6:** Explain the `config/` directory purpose.  
**A:** Contains configuration files (database.php, cors.php, auth.php, etc.). Values pulled from `.env` with defaults. Cached in production via `config:cache`.

### Intermediate

**Q7:** How does route model binding work? Where did you use it?  
**A:** Laravel auto-resolves Eloquent models from route parameters. Example: `Route::post('/onboarding/{invitation}/approve', ...)` — `$invitation` auto-resolves to `OnboardingInvitation` model by ID.

**Q8:** Explain the Service Container and Dependency Injection.  
**A:** Laravel's IoC container manages class dependencies. When a controller type-hints a service class (like `TenantIdService`), the container auto-resolves and injects it. Used `app(TenantIdService::class)` for manual resolution.

**Q9:** What are Laravel Service Providers?  
**A:** Central place for bootstrapping application services. `AppServiceProvider` registers bindings, `RouteServiceProvider` configures routes. They have `register()` and `boot()` methods.

**Q10:** What is the difference between `firstOrCreate` and `updateOrCreate`?  
**A:** `firstOrCreate` finds existing record or creates new one (used in rent generation to avoid duplicates). `updateOrCreate` finds and updates existing or creates new. We used `firstOrCreate` for monthly rent generation.

**Q11:** Explain `chunk()` and why you used it in rent generation.  
**A:** `chunk(100, fn)` processes records in batches of 100 to prevent memory exhaustion. Used in `RentService::generateMonthlyRents()` when iterating all active tenants — could be hundreds.

**Q12:** What is the difference between `hasOne` and `belongsTo`?  
**A:** `hasOne` — parent model has one child (Tenant hasOne currentBedAllocation). `belongsTo` — child model references parent via foreign key (Tenant belongsTo PgLocation via `pg_location_id`).

### Advanced

**Q13:** Explain how you structured the application using Services.  
**A:** Extracted business logic from controllers into service classes: `RentService` (rent generation, payment processing), `ElectricityBillingService` (bill splitting), `RoomAllocationService` (bed assignment), `TenantIdService` (ID generation). Controllers stay thin, services are testable and reusable.

**Q14:** How does `$app->usePublicPath(__DIR__)` work and why was it needed?  
**A:** On shared hosting, `public_html/` is the web root but Laravel expects `basePath/public/`. `usePublicPath()` overrides Laravel's assumption so `public_path()` resolves to `public_html/` instead of `pga1/public/`. Without it, the onboarding route's `file_get_contents(public_path('onboarding.html'))` failed with 500.

**Q15:** How did you handle the N+1 query problem?  
**A:** Used eager loading with `with()`. Example: `OnboardingInvitation::with(['pgLocation', 'createdByUser', 'documents'])` loads related data in 3 queries instead of N+1. Also used `whereHas()` for filtered relationship checks.

---

## 2. Authentication & Authorization

### Basic

**Q16:** What is Laravel Sanctum? How is it different from Passport?  
**A:** Sanctum provides lightweight token-based auth for SPAs and mobile apps. Passport is full OAuth2 (more complex). We chose Sanctum because we only need API tokens — no OAuth flows needed.

**Q17:** How does token-based authentication work in your project?  
**A:** User logs in → server creates a personal access token (stored in `personal_access_tokens` table) → token sent to client → client stores in localStorage → sent as `Authorization: Bearer <token>` header on every request.

**Q18:** What is the `auth:sanctum` middleware?  
**A:** Validates the Bearer token from the request header, resolves the authenticated user, and rejects with 401 if invalid/missing.

### Intermediate

**Q19:** Explain your role-based access control implementation.  
**A:** Three roles: super_admin, admin, tenant. Custom `EnsureRole` middleware accepts variadic roles: `middleware('role:admin,super_admin')`. Checks `$user->role` against allowed roles and verifies `is_active` status. Returns 403 if unauthorized.

**Q20:** How does the PG-level access control work?  
**A:** `EnsureAdminPgAccess` middleware checks if an admin has access to the specific PG location in the request. Uses `admin_pg_assignments` pivot table. Super admins bypass this check entirely.

**Q21:** How do you handle admin impersonation of tenants?  
**A:** Admin hits `/admin/tenants/{tenant}/impersonate` → creates a new Sanctum token for the tenant's user → returns the token to admin → admin's frontend temporarily uses this token to view the tenant dashboard. Original admin session is preserved in localStorage separately.

**Q22:** What is SANCTUM_STATEFUL_DOMAINS and why does it matter?  
**A:** Defines domains where Sanctum should use cookie-based (stateful) auth instead of token-based. In production, set to `pga1gurgaon.in,www.pga1gurgaon.in` so the same-origin dashboard can authenticate properly.

### Advanced

**Q23:** How would you implement permission-based access (not just role-based)?  
**A:** Could use a permissions table + role_permission pivot. Laravel Gates/Policies for fine-grained control. Example: `Gate::define('manage-electricity', fn($user) => $user->hasPermission('electricity.manage'))`. Could also use Spatie Laravel-Permission package.

**Q24:** How do you prevent horizontal privilege escalation?  
**A:** Admin can only access tenants in their assigned PGs (enforced via `EnsureAdminPgAccess`). Tenant can only see their own data (controller checks `$request->user()->tenant->id`). Route model binding doesn't automatically scope — we manually verify ownership.

---

## 3. Database & Eloquent ORM

### Basic

**Q25:** What are migrations? Why use them instead of raw SQL?  
**A:** Version-controlled database schema changes. Benefits: team collaboration, rollback capability, database-agnostic, trackable history. `migrations` table tracks which have run.

**Q26:** Explain Eloquent relationships in your project.  
**A:** User hasOne Tenant. Tenant belongsTo PgLocation. PgLocation hasMany Rooms. Room hasMany Beds. Tenant hasMany MonthlyRents. Tenant hasMany PaymentSubmissions. Bed belongsTo Room.

**Q27:** What are Eloquent scopes? Give an example from your project.  
**A:** Reusable query constraints. `Tenant::scopeActive($query)` returns `$query->where('status', 'active')`. Used as `Tenant::active()->chunk(...)` in rent generation to only process active tenants.

**Q28:** What is Soft Deleting? Where did you use it?  
**A:** Records aren't actually deleted — `deleted_at` timestamp is set. Used `SoftDeletes` trait on Tenant model. Admin can trash tenants (soft delete), view trash, restore, or force-delete permanently.

### Intermediate

**Q29:** Explain the `$casts` property and why you use it.  
**A:** Auto-converts attributes to/from PHP types. Example: `'current_rent' => 'decimal:2'`, `'joining_date' => 'date'`, `'offboarded_at' => 'datetime'`. Ensures consistent types when reading from DB.

**Q30:** What is the `$fillable` property? What is mass assignment vulnerability?  
**A:** `$fillable` whitelists which fields can be set via `Model::create($array)`. Without it, attackers could inject fields like `is_admin => true` in the request. Alternative is `$guarded`.

**Q31:** Explain `DB::transaction()` usage in your onboarding approval.  
**A:** Wraps multiple operations (create user, create tenant, allocate bed, transfer documents, update invitation) in a single transaction. If any step fails, everything rolls back — no partial data.

**Q32:** How do you handle database duplicates gracefully?  
**A:** Custom exception handler in `bootstrap/app.php` catches MySQL error 1062 (duplicate entry). Maps specific unique constraint names to user-friendly messages. Example: `unique_room_per_pg` → "This room number already exists in this PG location."

**Q33:** How did you design the bed allocation system?  
**A:** `tenant_bed_allocations` tracks history. Each record has `tenant_id`, `bed_id`, `allocated_at`, `vacated_at`, `is_current`. When tenant changes bed, old allocation gets `is_current=false` and `vacated_at` set, new record created. `Tenant::currentBedAllocation()` returns `hasOne` with `where('is_current', true)`.

### Advanced

**Q34:** How does the rent payment processing work with partial payments?  
**A:** `MonthlyRent` has `base_rent`, `paid_amount`, `due_amount`, `status`. When payment is verified, `RentService::processVerifiedPayment()` updates the ledger. If paid covers base rent + extra, it auto-marks electricity allocations as paid. Supports multiple partial payments summed up.

**Q35:** Explain the electricity bill splitting logic.  
**A:** `ElectricityBillingService` takes a room's bill and splits among active tenants in that room. Each tenant gets an `ElectricityBillAllocation` with their share. Amount = total_bill / number_of_tenants. Admin can also manually adjust a tenant's share.

**Q36:** What indexing strategy would you recommend for this schema?  
**A:** Composite indexes on: `monthly_rents(tenant_id, billing_month)` for uniqueness check; `tenant_bed_allocations(tenant_id, is_current)` for fast current bed lookup; `payment_submissions(tenant_id, status)` for filtered lists; `onboarding_invitations(token)` for token validation.

---

## 4. REST API Design

### Basic

**Q37:** What HTTP methods did you use and when?  
**A:** GET (read data), POST (create resources, actions like approve/reject), PUT (update entire resource), DELETE (remove resource). POST also used for non-CRUD actions like `/rents/generate`, `/tenants/{id}/impersonate`.

**Q38:** How did you version your API?  
**A:** URL prefix: `/api/v1/`. All routes wrapped in `Route::prefix('v1')`. Allows future `/v2/` without breaking existing clients.

**Q39:** How do you structure API responses?  
**A:** Consistent JSON format. Success: `{ "message": "...", "data": {...} }`. Error: `{ "message": "..." }` with appropriate HTTP status (400, 401, 403, 404, 422, 500). Validation errors return field-specific messages.

**Q40:** What is the difference between 401 and 403?  
**A:** 401 Unauthorized — not authenticated (no token or invalid token). 403 Forbidden — authenticated but lacks permission (wrong role, wrong PG access, deactivated account).

### Intermediate

**Q41:** How do you handle file uploads in your API?  
**A:** Use `multipart/form-data`. Laravel's `$request->file('selfie')` gives `UploadedFile`. Store with `$file->store('onboarding/{id}', 'local')`. Validate with rules: `'selfie' => 'required|image|max:5120'`. Serve files securely via authenticated endpoint that streams with `response()->file()`.

**Q42:** Why do you serve uploaded documents through a controller instead of direct URL?  
**A:** Documents (aadhaar, IDs) are stored in `storage/app/` (not public). Served via `/admin/documents/{id}/view` which checks auth + authorization before streaming. Prevents unauthorized access to sensitive documents.

**Q43:** Explain how pagination works in your listing endpoints.  
**A:** Manual pagination: accept `page` and `per_page` params. Use `skip(($page - 1) * $perPage)->take($perPage)`. Return pagination metadata: `{ total, per_page, current_page, last_page }`. Used in onboarding applications listing.

**Q44:** How do you handle filtering and searching?  
**A:** Query builder pattern: conditionally apply where clauses based on request params. Example: `if ($request->has('status')) $query->where('status', $request->status)`. Also `where('name', 'like', "%{$search}%")` for text search.

### Advanced

**Q45:** How would you rate-limit your API?  
**A:** Laravel's built-in `throttle` middleware: `throttle:60,1` (60 requests/minute). Can create custom rate limiters in `RateLimiter::for()`. Would apply stricter limits to login endpoint and public endpoints.

**Q46:** What is CORS? How did you configure it?  
**A:** Cross-Origin Resource Sharing — browser security that blocks requests from different domains. Configured in `config/cors.php`. Set `allowed_origins => ['*']` since frontend and API are same domain. Also enabled `supports_credentials => true` for Sanctum.

**Q47:** How would you add API documentation to this project?  
**A:** Could use: Scribe (auto-generates from code annotations), Swagger/OpenAPI (write spec, generate docs), or Postman collections. Scribe is Laravel-native and can read route definitions + docblocks.

---

## 5. Middleware

### Basic

**Q48:** What is middleware in Laravel?  
**A:** A filter that runs before/after HTTP requests. Can inspect, modify, or reject requests. Examples: authentication check, role verification, CORS headers, rate limiting.

**Q49:** What is the difference between global and route middleware?  
**A:** Global middleware runs on every request (defined in Kernel). Route middleware is assigned to specific routes/groups. Our `auth:sanctum` and `role:admin` are route middleware.

### Intermediate

**Q50:** Explain how your custom `EnsureRole` middleware works with variadic parameters.  
**A:** Defined as `middleware('role:admin,super_admin')`. The `handle()` method accepts `string ...$roles` (variadic). Checks if `$user->role` is in the provided roles array using `in_array()`. Also verifies `is_active` status.

**Q51:** How does middleware execution order work in groups?  
**A:** Middleware runs in order: first `auth:sanctum` (verifies token) → then `role:admin,super_admin` (checks role) → then `pg.access` if applied (checks PG assignment). If any returns early, subsequent middleware doesn't run.

### Advanced

**Q52:** How would you add logging middleware to track all admin actions?  
**A:** Create `LogAdminAction` middleware: capture request method, path, user_id, IP, timestamp. Store in `activity_logs` table. Apply to admin route group. Use `$next($request)` pattern, log after response for success/failure status.

---

## 6. Frontend (Vanilla JS)

### Basic

**Q53:** Why did you choose vanilla JS instead of React/Vue?  
**A:** No build step needed, simpler deployment (just static HTML), faster initial load, smaller bundle. Dashboard doesn't need complex state management — straightforward CRUD UI. Also easier to maintain on shared hosting without Node.js.

**Q54:** How does the API client module (`api.js`) work?  
**A:** Singleton object with methods for each API call. Manages tokens in localStorage. Has a central `request()` method that handles headers, auth token injection, error handling (401 redirects to login), and JSON parsing. All endpoint methods call `this.request()`.

**Q55:** How do you handle authentication on the frontend?  
**A:** Login → store token + user object in localStorage. `requireAuth()` guard function checks on page load. Token sent via `Authorization: Bearer` header on every request. 401 response clears auth and redirects to login.

### Intermediate

**Q56:** Explain the auto-detection pattern for API base URL.  
**A:** `const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') ? 'http://127.0.0.1:8000/api/v1' : '/api/v1'`. Detects environment at runtime — no config changes needed between local and production.

**Q57:** How does the public website dynamically load PG data?  
**A:** `index.html` fetches from `/api/v1/public/pg-locations` on page load. Renders property cards dynamically from JSON response. Includes fallback to `pg-data.json` static file if API is unreachable.

**Q58:** How did you handle file uploads from the frontend?  
**A:** Use `FormData` API. Append files + fields. Send via `fetch()` without `Content-Type` header (browser sets multipart boundary). The `api.upload()` method handles this with `isFormData = true` flag that skips JSON stringification.

### Advanced

**Q59:** How would you improve the frontend architecture?  
**A:** Could migrate to a framework (Vue/React) for better state management, component reuse, routing. Or use Alpine.js for lightweight reactivity. Add a service worker for offline capability. Implement proper client-side routing instead of separate HTML files.

---

## 7. File Storage & Media

### Basic

**Q60:** What storage disks does Laravel support?  
**A:** Local, public, S3, FTP, SFTP. Configured in `config/filesystems.php`. This project uses `local` disk (private storage) for sensitive documents and `public` disk (via symlink) for PG photos.

**Q61:** What is the difference between `local` and `public` disk?  
**A:** `local` stores in `storage/app/` (not web-accessible, secure). `public` stores in `storage/app/public/` which is symlinked to `public/storage/`. Documents (aadhaar, IDs) use local; PG photos use public.

### Intermediate

**Q62:** How did you create the storage symlink without SSH?  
**A:** Created a temporary `symlink.php` in `public_html/` that uses PHP's `symlink()` function to link `public_html/storage` → `pga1/storage/app/public`. Visited the URL once to execute, then immediately deleted the script.

**Q63:** How do you securely serve private documents?  
**A:** Store in `storage/app/` (not web-accessible). Serve via authenticated controller: `Storage::disk('local')->path($path)` + `response()->file()`. Only admins with valid tokens can access document view endpoints.

---

## 8. Deployment & DevOps

### Basic

**Q64:** Explain your deployment architecture on shared hosting.  
**A:** Two-folder structure: `pga1/` (Laravel app, outside web root) and `public_html/` (web-accessible). `index.php` bridges them. Advantage: vendor, .env, app code not web-accessible. Only public assets in document root.

**Q65:** Why put Laravel outside `public_html`?  
**A:** Security. If Laravel files were in `public_html`, `.env` (with DB credentials), `vendor/`, and `config/` would be potentially accessible via web. Keeping them outside ensures only `index.php` and static assets are exposed.

### Intermediate

**Q66:** How does `.htaccess` routing work in your setup?  
**A:** Apache mod_rewrite rules: (1) `RewriteRule ^$ index.html [L]` serves the landing page for root URL, (2) `RewriteCond %{REQUEST_FILENAME} !-f` skips real files (images, HTML, JS), (3) `RewriteRule ^ index.php [L]` sends everything else to Laravel. This allows static files to be served directly while API calls go through Laravel.

**Q67:** What DNS changes were needed for deployment?  
**A:** Migrated from GitHub Pages (A records: 185.199.108-111.153) to Serverbyt (A record: 185.151.30.162). Later changed nameservers from Hostinger parking to Serverbyt's `ns1.stackcp.com` / `ns2.stackcp.com` for SSL certificate issuance.

**Q68:** How do you handle environment differences (local vs production)?  
**A:** `.env` file for server-side config. Frontend uses hostname detection for API URL. Same codebase works in both environments without code changes. Only `.env` and domain config differ.

### Advanced

**Q69:** What would you change for a VPS/cloud deployment?  
**A:** Use Nginx (faster for static files), composer install on server, `php artisan migrate` via SSH, proper cron for scheduled tasks, Redis for cache/sessions, queue workers for background jobs, CI/CD pipeline (GitHub Actions → SSH deploy), SSL via Certbot.

**Q70:** How would you implement zero-downtime deployment?  
**A:** Symlink-based: deploy to `releases/v2/` folder, run migrations, then atomically switch symlink from `releases/v1/` to `releases/v2/`. Laravel Envoyer or Deployer can automate this. Keep old release for instant rollback.

**Q71:** How would you handle database migrations in production without SSH?  
**A:** Options: (1) Export local DB and import via phpMyAdmin (what we did), (2) Create a temporary `migrate.php` script that runs `Artisan::call('migrate')`, (3) Use a web-based admin panel to trigger migrations. Each has security tradeoffs.

---

## 9. Security

### Basic

**Q72:** How do you prevent SQL injection in Laravel?  
**A:** Eloquent and Query Builder use prepared statements (parameterized queries) by default. Never concatenate user input into raw queries. `DB::raw()` should be used cautiously with bindings.

**Q73:** How do you validate user input?  
**A:** Laravel's `$request->validate()` with rules: `'candidate_mobile' => 'required|string|digits:10'`, `'selfie' => 'required|image|max:5120'`. Returns 422 with field-specific errors on failure. Never trust client-side validation alone.

**Q74:** Why is `APP_DEBUG=false` important in production?  
**A:** When true, Laravel shows full stack traces, file paths, environment variables, and SQL queries to the browser. Attackers could see database credentials, internal file structure. Must be `false` in production.

### Intermediate

**Q75:** How do you sanitize error messages sent to the frontend?  
**A:** Custom exception handler intercepts `QueryException`. Checks if the message contains SQL keywords (`SQLSTATE`, `Query`) or is too long (>200 chars). Replaces with generic "Something went wrong" or specific user-friendly messages for known errors like duplicates.

**Q76:** How do you handle password security?  
**A:** Passwords hashed with bcrypt via `Hash::make()`. Never stored or logged in plain text. Generated hash for initial super admin via temporary `hash.php` script (deleted immediately). Default tenant password is their mobile number (forced to change on first login ideally).

**Q77:** What security measures exist in your `.htaccess`?  
**A:** `Options -MultiViews -Indexes` prevents directory listing and content negotiation attacks. Only files that physically exist are served directly — everything else goes through Laravel which has its own security stack.

### Advanced

**Q78:** How would you implement rate limiting on the login endpoint?  
**A:** `RateLimiter::for('login', fn($request) => Limit::perMinute(5)->by($request->ip()))`. Apply via `throttle:login` middleware on the login route. Returns 429 Too Many Requests with `Retry-After` header.

**Q79:** What is CSRF and why doesn't your API need it?  
**A:** Cross-Site Request Forgery tricks browsers into making unwanted requests. Not needed for token-based APIs because the token must be explicitly sent in headers — it's not auto-attached like cookies. Sanctum stateful mode does use CSRF for SPA auth.

**Q80:** How would you implement audit logging for compliance?  
**A:** We have `ActivityLog` model. Record: who (user_id), what (action), when (timestamp), on what (model_type, model_id), before/after values. Log in middleware or model observers. Immutable — never delete logs.

---

## 10. Business Logic Deep Dive

### Onboarding Flow

**Q81:** Walk me through the complete tenant onboarding flow.  
**A:**  
1. Admin creates invitation link (`POST /admin/onboarding/invite`) → generates 64-char random token  
2. Link shared: `pga1gurgaon.in/onboarding/{token}`  
3. Web route serves `onboarding.html` (or `verification.html` for existing tenants)  
4. Frontend validates token via `GET /api/v1/onboarding/{token}/validate`  
5. Candidate fills form, uploads documents (selfie, aadhaar, voter ID)  
6. `POST /api/v1/onboarding/{token}/submit` — creates application record, stores documents, creates user account  
7. Admin reviews in Onboarding section, views documents  
8. Admin clicks "Approve & Assign" → selects PG, bed, rent, joining date  
9. `POST /admin/onboarding/{invitation}/approve` → in transaction: generates tenant ID, creates tenant profile, allocates bed, transfers documents, handles referral  

**Q82:** How does the bulk onboarding link work?  
**A:** A single link with `link_type: 'bulk'` can be used by multiple candidates. Each submission creates a separate `OnboardingInvitation` record (child) linked to the original. The parent token remains active until expiry. This allows sharing one link in a WhatsApp group.

**Q83:** How do you handle link expiration and single-use links?  
**A:** `OnboardingInvitation` has `expires_at` timestamp. `isExpired()` method checks `now() > expires_at`. Single-use links (`link_type: 'single'`) get `status: 'submitted'` after first use — subsequent attempts are rejected with "This link has already been used."

### Rent & Payments

**Q84:** How does monthly rent generation work?  
**A:** Admin clicks "Generate Rent" for a billing month. `RentService::generateMonthlyRents()` iterates all active tenants (chunked), uses `firstOrCreate` to avoid duplicates. Creates `MonthlyRent` record with base_rent from tenant's `current_rent`, calculates due date (10th of month).

**Q85:** Explain the payment verification flow.  
**A:**  
1. Tenant submits payment screenshot via `POST /tenant/payments` (FormData with image)  
2. Admin sees pending payments, views screenshot  
3. Admin verifies with actual received amount or rejects with reason  
4. On verify: `RentService::processVerifiedPayment()` updates MonthlyRent ledger, recalculates due, auto-marks electricity if fully paid  
5. Supports partial payments — multiple submissions summed against total due  

**Q86:** How does rent change with history tracking work?  
**A:** `RentService::changeRent()` creates a `TenantRentHistory` record (previous_rent, new_rent, effective_date, reason, changed_by) then updates `tenant.current_rent`. Complete audit trail of all rent changes.

### Electricity Billing

**Q87:** How is electricity billing split among roommates?  
**A:** Admin enters total room bill + meter readings. `ElectricityBillingService` divides equally among active tenants in that room. Each tenant gets an `ElectricityBillAllocation`. Amount = total / number_of_roommates. Admin can manually adjust individual shares.

**Q88:** How does electricity payment integrate with rent payments?  
**A:** When a verified rent payment exceeds base_rent, the excess auto-marks electricity allocations as paid (in chronological order). If monthly rent is fully paid, all electricity for that month is also marked paid. This handles the common case where tenants pay rent + electricity in one transaction.

---

## 11. Error Handling

**Q89:** How do you handle errors globally in Laravel?  
**A:** In `bootstrap/app.php` → `withExceptions()`. Register `renderable()` callbacks for specific exception types. `QueryException` with error code 1062 → friendly duplicate message. 1451/1452 → foreign key messages. API always returns JSON errors.

**Q90:** How do you prevent technical errors from reaching the user?  
**A:** Two layers: (1) Backend: exception handler catches SQL errors, returns sanitized messages. (2) Frontend: `api.js` checks if response message contains `SQLSTATE`/`Query`/is >200 chars, replaces with "Something went wrong."

**Q91:** What happens when a bed is already occupied during approval?  
**A:** Before approving, checks `$bed->status !== 'available'`. If occupied, returns 422: "The selected bed is no longer available. Please choose another bed." Prevents race conditions in concurrent approvals.

---

## 12. Performance & Scalability

**Q92:** How would you optimize database queries for a larger user base?  
**A:** Add proper indexes (composite for frequent queries), use eager loading consistently, implement caching (Redis) for static data like PG locations, paginate all list endpoints, use database connection pooling.

**Q93:** How would you implement caching in this project?  
**A:** Cache public PG data: `Cache::remember('pg-locations', 3600, fn() => PgLocation::all())`. Invalidate on update. Cache tenant dashboard data. Use Redis for session storage. Cache frequently accessed config.

**Q94:** What bottlenecks do you see at scale?  
**A:** Rent generation for 1000+ tenants (solved with chunking). File uploads on shared hosting (move to S3). Single database server (add read replicas). No queue system (add Redis + workers for async tasks like notifications). No CDN for static assets.

**Q95:** How would you handle background tasks?  
**A:** Laravel Queues with Redis/database driver. Jobs for: sending notifications after payment verification, generating monthly rent (scheduled), processing uploaded images (thumbnails), sending expiry reminders for onboarding links.

---

## 13. Design Patterns & Architecture

**Q96:** What design patterns did you use?  
**A:**  
- **Service Pattern** — Business logic in RentService, ElectricityBillingService, etc.  
- **Repository-like** — Eloquent models as data access layer  
- **Middleware Pipeline** — Request filtering chain  
- **Strategy** — Different link types (bulk, single, existing) handled differently  
- **Observer-like** — Exception renderable callbacks  
- **Singleton** — API client object in frontend JS  

**Q97:** Why separate Services from Controllers?  
**A:** Single Responsibility. Controllers handle HTTP concerns (validation, response formatting). Services contain business rules (rent calculation, payment processing). Benefits: testable without HTTP, reusable across multiple controllers, easier to modify business rules without touching request handling.

**Q98:** How would you refactor this project for microservices?  
**A:** Split by domain: Auth Service, Tenant Service, Payment Service, Onboarding Service. Each with own DB. Communicate via REST/gRPC/events. Shared nothing architecture. But for this scale (100 tenants, 5 PGs), monolith is perfectly appropriate — microservices would be over-engineering.

---

## 14. Scenario-Based Questions

**Q99:** A tenant says they paid but admin can't see the payment. How do you debug?  
**A:** Check: (1) Did tenant actually submit? Check `payment_submissions` table, (2) Was file too large? Check PHP upload limits, (3) Network error? Check browser console, (4) Token expired? Check if 401 was returned, (5) Check Laravel log: `storage/logs/laravel.log`.

**Q100:** The onboarding form works locally but gets 500 in production. How do you fix?  
**A:** Check `pga1/storage/logs/laravel.log` for error details. Common causes: (1) `public_path()` pointing wrong (need `usePublicPath`), (2) Storage permissions (775 on storage/), (3) Missing PHP extensions, (4) DB connection issue. We actually faced this — fixed by adding `$app->usePublicPath(__DIR__)`.

**Q101:** Two admins try to approve the same tenant for the same bed simultaneously. What happens?  
**A:** First approval succeeds (creates allocation, marks bed as occupied). Second approval fails with 422: "The selected bed is no longer available" because the code checks `$bed->status !== 'available'` before proceeding. Transaction + status check prevents double-booking.

**Q102:** How would you add email/SMS notifications to this system?  
**A:** Laravel Notifications with channels (mail, SMS via Twilio). Create: `TenantApproved`, `PaymentVerified`, `RentDue` notification classes. Trigger after relevant actions. Use queues for async sending. Store notification preferences per user.

**Q103:** A tenant wants to move from one PG to another. How does the system handle it?  
**A:** Admin uses "Change Bed" feature: (1) Current allocation marked `is_current=false`, `vacated_at` set, (2) Old bed status → available, (3) New allocation created at new PG/bed, (4) Tenant's `pg_location_id` updated. History preserved in `tenant_bed_allocations`.

---

## 15. Deployment-Specific Questions

**Q104:** Why did the SQL export fail with encoding errors?  
**A:** Windows PowerShell's `>` redirect operator outputs UTF-16 by default. phpMyAdmin expects UTF-8. Fix: use `mysqldump --result-file=file.sql` which writes directly to file bypassing shell encoding.

**Q105:** Why did you need a custom `index.php` for shared hosting?  
**A:** Standard Laravel: `public/` is the web root inside the project. Shared hosting: `public_html/` is fixed web root, separate from app code. Custom `index.php` bridges them by setting `$basePath = dirname(__DIR__) . '/pga1'` and overriding storage/public paths.

**Q106:** How does the `.htaccess` handle both static HTML and Laravel API on the same domain?  
**A:** Priority rules: (1) Root URL → `index.html` (static website), (2) Real files/directories → served directly (images, CSS, JS, dashboard HTML), (3) Everything else → `index.php` (Laravel handles API routes). This is why `/api/v1/...` goes to Laravel but `/dashboard/login.html` serves the file directly.

**Q107:** What is the `vendor/` folder and why must it be uploaded?  
**A:** Contains all Composer dependencies (Laravel framework, Sanctum, Carbon, etc.). Normally generated by `composer install`. Since shared hosting has no SSH/CLI access, we must upload the entire `vendor/` folder (~30-50MB). Without it: "Class not found" errors everywhere.

**Q108:** How does SSL auto-activate on Serverbyt?  
**A:** Serverbyt issues free Wildcard SSL via Let's Encrypt when nameservers point to their servers (`ns1.stackcp.com`, `ns2.stackcp.com`). Once DNS propagation completes and Serverbyt detects their nameservers are authoritative, they auto-issue and install the certificate. "Force HTTPS" toggle redirects all HTTP → HTTPS.

---

## 16. Quick Fire (Expect These!)

| Question | Answer |
|----------|--------|
| What PHP version does this project need? | 8.2+ |
| What auth system do you use? | Laravel Sanctum (token-based) |
| How many roles? | 3 — super_admin, admin, tenant |
| What ORM? | Eloquent |
| How do you handle file uploads? | FormData + Storage::disk('local') |
| What's the DB host in production? | sdb-81.hosting.stackcp.net (NOT localhost) |
| What's in the `personal_access_tokens` table? | Sanctum tokens (tokenable_id, token hash, abilities, expiry) |
| What does `SoftDeletes` add? | `deleted_at` column, excludes from queries by default |
| How are passwords stored? | bcrypt hash via `Hash::make()` |
| What is `firstOrCreate`? | Find by attributes or create if not found (atomic) |
| What is eager loading? | Loading relationships upfront with `with()` to avoid N+1 |
| What HTTP status for validation error? | 422 Unprocessable Entity |
| What HTTP status for forbidden? | 403 |
| What HTTP status for not found? | 404 |
| What is a pivot table? | `admin_pg_assignments` — many-to-many between users and pg_locations |

---

*Total: 108 questions covering Laravel, API design, auth, database, frontend, deployment, security, and system design.*
