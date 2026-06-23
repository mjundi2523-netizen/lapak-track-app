# Rebuild All Lost LapakTrack Source Files

## Context
All application source code was accidentally deleted. MySQL database (`lapak_track`) is fully intact with 15 migrations applied, all data (57 bills, 35 payments, 20 dealers, 25 stalls, etc.), and `bill_id` values populated. The `vendor/`, `node_modules/`, `.env`, and `bootstrap/cache/` remain on disk. Only `app/Livewire/Profile/ShowProfile.php` and its view survived.

**Key versions** (from `vendor/composer/installed.json`):
- Laravel **13.15.0**, Livewire **3.8.1**, MaryUI **2.8.3**, Filament **4.11.7**, Breeze **2.4.2**

## Task 1: Foundation Infrastructure (~12 files)
Create the files that make Laravel boot. Nothing else works without these.

| File | Notes |
|------|-------|
| `composer.json` | PSR-4: `"App\\": "app/"`. Versions: `laravel/framework: ^13.0`, `livewire/livewire: ^3.0`, `robsontenorio/mary: ^2.0`, `filament/filament: ^4.0`, `laravel/breeze: ^2.0` |
| `artisan` | Standard Laravel 13 artisan bootstrap |
| `bootstrap/app.php` | `Application::configure()` with `withRouting()`, `withMiddleware()`, providers |
| `public/index.php` | Standard Laravel 13 front controller |
| `public/.htaccess` | Standard Apache rewrite rules |
| `bootstrap/providers.php` | Returns `[AppServiceProvider::class, AdminPanelProvider::class]` |
| `config/app.php` | Standard Laravel 13 config, `name => LapakTrack` |
| `config/database.php` | Standard with MySQL default |
| `config/auth.php` | Standard Breeze auth |
| `config/cache.php`, `session.php`, `queue.php`, `filesystems.php`, `mail.php`, `logging.php`, `services.php`, `broadcasting.php` | Standard Laravel 13 defaults |
| `app/Providers/AppServiceProvider.php` | Minimal register + boot |

**Verify**: `php artisan --version` returns `Laravel Framework 13.15.0`

## Task 2: Models (9 files)
All models use `#[Fillable([...])]` PHP 8 attribute and custom `$primaryKey`.

| Model | Path | PK | Key Relationships |
|-------|------|----|-------------------|
| `User` | `app/Models/User.php` | `id` | Standard Breeze user |
| `PaymentTerm` | `app/Models/PaymentTerm.php` | `ptid` | `hasMany(Stall)`, `createdBy()`, `modifiedBy()` |
| `AddOn` | `app/Models/AddOn.php` | `aoid` | `belongsToMany(Stall)` via `stall_add_ons` |
| `Stall` | `app/Models/Stall.php` | `sid` | `belongsTo(PaymentTerm)`, `belongsToMany(AddOn)` via `stall_add_ons`, `hasMany(DealerStall)`, `activeRentals()` |
| `StallAddOn` | `app/Models/StallAddOn.php` | `saoid` | `belongsTo(Stall)`, `belongsTo(AddOn)` |
| `Dealer` | `app/Models/Dealer.php` | `did` | `hasMany(DealerStall)`, casts: `birth_date` => `date` |
| `DealerStall` | `app/Models/DealerStall.php` | `dsid` | `belongsTo(Dealer)`, `belongsTo(Stall)`, `hasMany(DealerBill)` |
| `DealerBill` | `app/Models/DealerBill.php` | `dbid` | `belongsTo(DealerStall)`, `hasMany(DealerPayment)`, `recalculateBillingStatus()` method, `getRouteKeyName() => 'dbid'` |
| `DealerPayment` | `app/Models/DealerPayment.php` | `dpid` | `belongsTo(DealerBill)`, `voidedBy()` => `belongsTo(User)`, `getRouteKeyName() => 'dpid'` |

**Verify**: `php artisan tinker` can query `Dealer::count()` returning 20

## Task 3: Migrations (15 files)
Filenames must EXACTLY match the `migrations` table (verified from MySQL):
```
0001_01_01_000000_create_users_table
0001_01_01_000001_create_cache_table
0001_01_01_000002_create_jobs_table
2026_06_14_084813_create_dealer_table
2026_06_14_084814_create_payment_terms_table
2026_06_14_084815_create_add_ons_table
2026_06_14_084816_create_stall_table
2026_06_14_084817_create_terms_add_ons_table
2026_06_14_084818_create_dealer_stall_table
2026_06_14_084819_create_dealer_bills_table
2026_06_14_084820_create_dealer_payment_table
2026_06_14_100000_replace_terms_add_ons_with_stall_add_ons
2026_06_14_110000_add_aoid_to_stall_and_drop_stall_add_ons
2026_06_14_120000_create_stall_add_ons_pivot_table
2026_06_14_100000_add_bill_id_to_bills_and_payments
```
Schema from `check_schema.php` output is the ground truth. Key: custom PKs (auto-increment `bigint unsigned`), `enum` columns, `bill_id varchar(20) unique nullable`, `paid_amount decimal(15,2) default 0.00`, audit fields (`created_by`, `modified_by`).

**SAFETY**: Do NOT run `migrate:fresh`. Only `php artisan migrate` which should output "Nothing to migrate".

**Verify**: `php artisan migrate:status` shows all 15 as "Ran"

## Task 4: Services (2 files)

### `app/Services/BillIdGenerator.php`
- `generate(string $table, string $entityCode, Carbon $date): string`
- Format: `[EntityCode]-[YYMM]-[Sequence]` (e.g., `MTR-2606-0001`)
- MTR = main stall rent, ATR = add-on transactions
- Use `DB::transaction()` with `lockForUpdate()` for concurrency safety

### `app/Services/BillGenerationService.php`
- `generateBillsForDealerStall(DealerStall $ds)`: creates MTR main bill + ATR add-on bills grouped by frequency
- Uses `DB::transaction()` for write safety
- Called from `regen_bills.php` confirms class and method signature

## Task 5: Routes (2 files)

### `routes/web.php`
```
GET / → redirect to /dashboard
GET /dashboard → view('dashboard') (name: dashboard)
GET /aturan-bayar → IndexPaymentTerms (name: payment-terms.index)
GET /aturan-bayar/create → CreatePaymentTerm (name: payment-terms.create)
GET /aturan-bayar/{paymentTerm}/edit → EditPaymentTerm (name: payment-terms.edit)
GET /biaya-lain-lain → IndexAddOns (name: add-ons.index)
GET /biaya-lain-lain/create → CreateAddOn (name: add-ons.create)
GET /biaya-lain-lain/{addOn}/edit → EditAddOn (name: add-ons.edit)
GET /lapak → IndexStalls (name: stalls.index)
GET /lapak/create → CreateStall (name: stalls.create)
GET /lapak/{stall}/edit → EditStall (name: stalls.edit)
GET /pedagang → IndexDealers (name: dealers.index)
GET /pedagang/create → CreateDealer (name: dealers.create)
GET /pedagang/{dealer} → ShowDealer (name: dealers.show)
GET /pedagang/{dealer}/edit → EditDealer (name: dealers.edit)
GET /tagihan → IndexBills (name: bills.index)
GET /tagihan/{dealerBill} → ShowBill (name: bills.show)
GET /pembayaran → IndexPayments (name: payments.index)
GET /pembayaran/create → CreatePayment (name: payments.create)
GET /pembayaran/{payment}/void → VoidPayment (name: payments.void)
Profile routes (edit/update/destroy)
```
All app routes use `['web', 'auth', 'verified']` middleware.

### `routes/auth.php`
Standard Breeze auth routes (login, register, forgot-password, reset-password, verify-email, confirm-password, logout)

**Verify**: `php artisan route:list` shows all routes

## Task 6: Livewire Components (17 PHP files + 18 blade views)

All components follow the pattern from `ShowProfile.php`:
```php
#[Layout('layouts.app')]
class ComponentName extends Component
{
    use Toast;
    // properties with #[Validate], mount(), actions, render()
}
```

### PaymentTerms (3 components)
- `IndexPaymentTerms` - x-table listing, search, edit/delete actions
- `CreatePaymentTerm` - form: term_name, frequency(enum), price. `DB::transaction()` on save
- `EditPaymentTerm` - same form, accepts `PaymentTerm $paymentTerm` in mount

### AddOns (3 components)
- `IndexAddOns` - x-table listing
- `CreateAddOn` - form: add_on, price, frequency(enum)
- `EditAddOn` - accepts `AddOn $addOn`

### Stalls (3 components)
- `IndexStalls` - x-table with is_active badge, block search
- `CreateStall` - form: block, description, payment term select, add-on checkboxes
- `EditStall` - accepts `Stall $stall`

### Dealers (4 components)
- `IndexDealers` - x-table with status badge, search
- `CreateDealer` - complex form: NIK, name, birth_date, address, phones, product_type, scan_id upload, stall selection, rent dates. Triggers `BillGenerationService`
- `ShowDealer` - detail view: dealer info, stall assignments, bills summary
- `EditDealer` - accepts `Dealer $dealer`

### Bills (2 components)
- `IndexBills` - x-table with `:row-decoration`, status badges, filter by status. **Batched paid-totals** query: `DealerPayment::selectRaw('dbid, SUM(paid_amount) as total')->whereIn('dbid', $billIds)->where('is_voided', false)->groupBy('dbid')->get()`
- `ShowBill` - accepts `DealerBill $dealerBill`, payment history with `bg-error/20` for voided rows, calls `recalculateBillingStatus()`

### Payments (3 components)
- `IndexPayments` - x-table with `bg-error/20` row decoration for voided rows
- `CreatePayment` - modal bill picker with search filter, auto-select bill from URL `?bill=X`, generates `bill_id` via `BillIdGenerator`
- `VoidPayment` - accepts `DealerPayment $payment`, void form with reason, sets is_voided/voided_at/voided_by

**Views** (in `resources/views/livewire/{module}/`):
- All use MaryUI components: `x-header`, `x-card`, `x-table`, `x-input`, `x-select`, `x-button`, `x-badge`, `x-modal`
- `@scope('cell_key', $row)` for custom cell rendering
- `:row-decoration` for conditional row CSS

## Task 7: Layouts and Dashboard (3 view files)

### `resources/views/layouts/app.blade.php`
- HTML5 with `@vite`, `@livewireStyles`, `@livewireScripts`
- MaryUI `<x-nav>` with sticky/fullWidth, brand (icon `o-square-3-stack-3d` + "LapakTrack")
- Responsive sidebar via `@include('layouts.sidebar')`
- User dropdown with Profile/Logout
- `<x-toast />` for notifications
- Main content `$slot`

### `resources/views/layouts/sidebar.blade.php`
- `<x-menu activate-by-route>` with two separator groups:
  - **Master Data**: Dashboard (`o-home`), Aturan Bayar (`o-banknotes`), Biaya Lain-lain (`o-plus-circle`), Lapak (`o-building-storefront`)
  - **Transaksi**: Registrasi Pedagang (`o-user-plus`), Tagihan (`o-document-text`), Pembayaran (`o-credit-card`)

### `resources/views/dashboard.blade.php`
- Stats cards: active dealers, occupied stalls, unpaid bills, payments this month

## Task 8: Auth Controllers + Views (~15 files)

### Controllers (Breeze standard)
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- `app/Http/Controllers/Auth/NewPasswordController.php`
- `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- `app/Http/Controllers/Auth/VerifyEmailController.php`
- `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
- `app/Http/Controllers/Auth/PasswordController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/ProfileUpdateRequest.php`

### Views
- `resources/views/auth/` - login, register, forgot-password, reset-password, verify-email, confirm-password
- `resources/views/profile/edit.blade.php`
- `resources/views/layouts/guest.blade.php`

These are standard Breeze 2.4.2 scaffolding.

## Task 9: Filament Admin Panel (5 files)
- `app/Providers/Filament/AdminPanelProvider.php` - panel at `/admin`
- `app/Filament/Resources/PaymentTermResource.php`
- `app/Filament/Resources/PaymentTerms/Pages/ListPaymentTerms.php`
- `app/Filament/Resources/PaymentTerms/Pages/CreatePaymentTerm.php`
- `app/Filament/Resources/PaymentTerms/Pages/EditPaymentTerm.php`

## Task 10: Frontend Assets (5 files)
- `vite.config.js` - Laravel Vite plugin with `resources/css/app.css` + `resources/js/app.js`
- `resources/css/app.css` - TailwindCSS v4 + DaisyUI
- `resources/js/app.js` - Alpine.js imports
- `package.json` - `tailwindcss`, `daisyui`, `vite`, `laravel-vite-plugin`
- `tailwind.config.js` (if needed for TailwindCSS v4)

**Verify**: `npm run build` succeeds

## Task 11: DatabaseSeeder (1 file, for completeness only)
- `database/seeders/DatabaseSeeder.php` - NOT to be executed (data exists)

## Task 12: Final Verification
1. `php artisan migrate` → "Nothing to migrate"
2. `php artisan migrate:status` → all 15 "Ran"
3. `php artisan route:list` → all routes present
4. `php artisan view:clear && php artisan view:cache` → all views compile
5. Row counts unchanged: `php check_seed.php`
6. Browser test: login → dashboard → navigate all 18 routes

## Rejected Alternatives
- **Git recovery**: Impossible - only 1 commit with profile files, no custom code tracked
- **`php artisan breeze:install` to regenerate auth**: Risky with existing files, manual creation is safer
- **`migrate:fresh` + `db:seed`**: Unnecessary and destructive - database already has all data
- **Recreating brief/ files**: Not needed for application functionality
