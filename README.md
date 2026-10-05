# Inventory Management System

Deployment to Vercel: see [docs/VERCEL_DEPLOYMENT.md](docs/VERCEL_DEPLOYMENT.md) for the PHP function, Vite assets, database, and environment setup.

A Laravel 13 inventory application with an Inertia/React frontend. The project is a conversion of the original procedural PHP application: Laravel owns routing, authentication, validation, policies, persistence, and the domain rules, while Inertia passes page data to React without requiring a separate API for every screen.

The current web contract is:

- `GET /login` and `POST /login` for username/password authentication (owned by Laravel Fortify, throttled 5 attempts/minute)
- `POST /logout` to end the session
- `GET /register` and `POST /register` for public registration (new accounts get the `User` role)
- `GET /forgot-password`, `POST /forgot-password`, `GET /reset-password/{token}`, and `POST /reset-password` for password recovery by email
- `GET /account` plus Fortify's `PUT /user/profile-information` and `PUT /user/password` for profile and password maintenance
- `GET /dashboard` for the authenticated dashboard
- REST-style resources for `/categories`, `/items`, and `/batches`; `/activity-logs` is a read-only history page
- `/reports` for low-stock, expiry, and activity summaries
- `/notifications` for the signed-in user's notifications
- `/users` for administrator-only user management
- `PATCH /items/{item}/archive` for the explicit item archive action, plus notification recent/read/read-all endpoints

Inertia page names are `Auth/Login`, `Auth/Register`, `Auth/ForgotPassword`, `Auth/ResetPassword`, `Auth/ConfirmPassword` (guest layout), and `Dashboard`, `Account`, `Items/Index`, `Categories/Index`, `Batches/Index`, `ActivityLogs/Index`, `Users/Index`, `Reports/Index`, `Notifications/Index` (application layout).

## Requirements

Install the following before starting:

- PHP 8.3 or newer, with the PDO MySQL extension and PDO SQLite for the test suite
- Composer
- Node.js `^20.19.0` or `>=22.12.0` (required by Vite 7)
- npm
- MySQL 8 (or a compatible MariaDB release)

For the local course setup, XAMPP can provide Apache, PHP, and MySQL, but Laravel is served by `php artisan serve` during development. The Laravel document root is `public/`; the old project-level `index.php` is not the current application entry point.

## Setup

1. Install PHP and Composer dependencies:

   ```bash
   composer install
   ```

2. Install the JavaScript dependencies:

   ```bash
   npm install
   ```

3. Create the local environment file and generate an application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   On PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

4. Start MySQL and create a database named `inventory_system` with a UTF-8/UTF-8 MB4-capable collation. The default example settings target `localhost:3306` with the local MySQL user; replace the username and password with your local values in `.env`. Do not commit `.env`.

5. Apply the Laravel migrations and development seeders:

   ```bash
   php artisan migrate --seed
   ```

   The migrations create the Laravel session, cache, queue, and password-reset tables as well as the inventory tables. The seeders create the Spatie roles/permissions (`RolesAndPermissionsSeeder`) and an admin account. Override `ADMIN_USERNAME` and `ADMIN_PASSWORD` in your local `.env` if needed. In the `local` and `testing` environments, the seeders also create these demo accounts:

   | Role | Username | Password | Email |
   | --- | --- | --- | --- |
   | Admin | `admin` | `Admin@1234` | `admin@example.test` |
   | Employee | `employee` | `Employee@1234` | none (legacy account — add one at `/account`) |
   | Employee | `employee2` | `Employee2@1234` | none (legacy account) |
   | User | `viewer` | `Viewer@1234` | `viewer@example.test` |

   These credentials are for local development; the demo accounts are skipped in other environments. Repeated seeding preserves an existing administrator's profile and password. If `ADMIN_USERNAME` belongs to a non-admin account, seeding stops without promoting or modifying that account; use the trusted administrator's current username, or an unused username for initial provisioning. Employees can use inventory features, `viewer` is view-only, and only admins can manage users at `/users`.

   For an existing local database, create the demo accounts with `php artisan db:seed --class=EmployeeSeeder`, and a demonstrable catalog with `php artisan db:seed --class=DemoInventorySeeder` (skipped when items already exist). Password-recovery emails are written to `storage/logs/laravel.log` with the default `MAIL_MAILER=log`; configure SMTP in `.env` for a deployment.

6. Build the frontend assets and start the development servers:

   ```bash
   npm run build
   composer run dev
   ```

   `composer run dev` runs `php artisan serve`, the queue listener, the log viewer, and Vite. Open `http://localhost:8000`. If Vite reports a busy port, use the alternate URL printed by Vite and keep the Laravel URL at port 8000.

   For a production-like local check, build once with `npm run build` and serve Laravel with `php artisan serve`. If the application stores uploaded files publicly, create the storage link with `php artisan storage:link`.

## Environment

`.env.example` is safe to share: it contains no real passwords, API keys, or production hosts. The important local settings are:

| Setting | Local default | Purpose |
| --- | --- | --- |
| `APP_ENV` | `local` | Enables local development behavior |
| `APP_DEBUG` | `true` | Shows useful errors locally; turn off in production |
| `APP_URL` | `http://localhost:8000` | Base URL for generated links and redirects |
| `DB_CONNECTION` | `mysql` | Uses the MySQL connection |
| `DB_DATABASE` | `inventory_system` | Application schema |
| `SESSION_DRIVER` | `database` | Persists login state in the `sessions` table |
| `CACHE_STORE` | `database` | Uses the Laravel cache tables |
| `QUEUE_CONNECTION` | `database` | Persists queued work in the `jobs` tables |
| `QUEUE_CONNECTION` | `sync` in PHPUnit | Keeps tests local and immediate |

If the database session or cache tables are unavailable, run `php artisan migrate` before starting the server. `file` is also a valid local session/cache choice, but it should be configured consistently in `.env`.

## Database, migrations, and seeders

The Laravel application uses migrations as the source of truth for its schema. Run the following while developing:

```bash
php artisan migrate
php artisan migrate:status
php artisan db:seed
```

`php artisan migrate:fresh --seed` is useful for a disposable test/demo database, but it drops all tables and data. Never use it against a database containing data that must be preserved.

The conversion keeps the legacy vocabulary so existing data can be mapped without a translation layer:

- `roles` (spatie/laravel-permission): `id`, `name` (`Admin`, `Employee`, `User`), `guard_name`, timestamps — legacy IDs preserved
- `users`: `full_name`, unique `username`, nullable unique `email`, `password_hash`, `remember_token`, and timestamps
- `categories`: unique `category_name` and timestamps
- `items`: `sku`, `name`, `category_id`, `price`, `quantity`, `low_stock_threshold`, and `is_deleted`
- `batches`: `item_id`, `batch_number`, `quantity`, and `expiry_date`
- `activity_log`: actor/item references, action type, old/new quantities, description, and timestamp
- `notifications`: recipient, type, title, message, link, `is_read`, and timestamp

Spatie's `permissions`, `model_has_roles`, `model_has_permissions`, and `role_has_permissions` tables hold the RBAC data; the legacy `users.role_id` column and `roles.role_name` were migrated into them and then dropped (backfill `2026_10_05_000003_*`, drop `2026_10_05_000009_*`). Timestamp history: `users`, `batches`, `activity_log`, and `notifications` already recorded `created_at`, which is preserved; `categories` had no timestamp columns and no historical creation times exist, so `2026_10_05_000006_add_domain_table_timestamps` backfills both columns with the migration time.

Authentication is by **username** against `password_hash` (Fortify's `username` config and `User::getAuthPasswordName()` keep the legacy columns); a request containing only an email is not a valid login. The `email` column exists for password recovery and is editable on the account page, and the nullable `remember_token` supports the login form's persistent “remember me” session, while ordinary requests remain session-authenticated.

## Authentication and authorization

Authentication is powered by **Laravel Fortify**: login/logout, public registration, password recovery by email, and profile/password updates. `config/fortify.php` selects the `web` guard, the `username` login field, `/dashboard` as the post-login home, and the feature set (registration, password reset, profile and password updates; email verification, two-factor authentication, and passkeys stay disabled). `App\Providers\FortifyServiceProvider` binds `app/Actions/Fortify/*` and renders the custom React pages for each auth screen under `resources/js/Layouts/GuestLayout.jsx`; the custom login controller was removed so Fortify owns those endpoints. Registration always assigns the **User** role and ignores any submitted role/permission fields. Login is throttled to five attempts per minute per username/IP.

Role-based access is powered by **spatie/laravel-permission**. Roles are `Admin`, `Employee`, and `User`; permissions are granular (`view/create/update/delete` for categories, items, batches, and users, plus `view reports`, `view activity logs`, `view notifications`, and `manage notifications`) and are seeded by `database/seeders/RolesAndPermissionsSeeder.php`. Backend routes combine `auth` with the `role`/`permission` middleware aliases registered in `bootstrap/app.php`; policies and form requests re-check with `$user->can(...)`. `HandleInertiaRequests` shares `auth.roles` and `auth.permissions`, and React renders navigation and mutation buttons conditionally from them (`resources/js/Utils/auth.js`). UI visibility is not an authorization boundary.

Capability matrix: Admins can do everything, including user management at `/users`. Employees use the inventory category, item, and batch resources plus reports and activity logs. Users (including everyone who self-registers) view inventory and reports and manage only their own notifications. An authenticated user without the matching permission receives a forbidden response on direct requests. Ownership checks keep notifications recipient-scoped, and the last-admin and self-delete rules remain (the last admin cannot be deleted or demoted, and an admin cannot delete their own account).

## Domain behavior

### Categories

A category has a unique `category_name`. Creating or renaming a category to an existing name is rejected. A category that still has linked item records cannot be deleted; archive or move those items first (the Laravel policy may retain archived rows for foreign-key/audit safety). Item counts and active reports ignore items marked deleted.

### Items and stock

An item is identified by a unique SKU and has a category, price, current quantity, and low-stock threshold. Prices and quantities cannot be negative, and a category must exist. Creating, editing, and deleting items is performed through the `/items` resource and is validated again on the server.

Stock status is based on `quantity <= low_stock_threshold`; an item at its threshold is low stock. Inventory value is the sum of `price * quantity` for active items. The item delete operation is a soft delete (`is_deleted`), so archived items are excluded from normal lists, counts, and reports while remaining available for audit history; the public application does not currently provide a restore route.

### Batches and expiry

A batch belongs to an active item and records its batch number, quantity, and expiry date. Quantities cannot be negative and expiry dates must be valid dates. The default expiry watch/report window is 30 days; reports can request a larger bounded window. A batch must not reference a missing item.

### Activity history

Inventory mutations create an `activity_log` record. The log identifies the actor and item when available, the action (`create`, `update`, `delete`, `stock_in`, or `stock_out`), the old and new quantities where applicable, a human-readable description, and the time. User deletion or item deletion must not erase history that is needed for auditing.

### Notifications

Notifications are recipient-scoped. A signed-in user may list and read only notifications addressed to that user; one user must not be able to mark another user's notification as read by changing an ID. Item and batch mutations fan out notifications to every user, including the actor; category and user-management mutations do not create the same broadcast alerts. The notification table is a Laravel migration concern, not something supplied by the original SQL dump.

### Dashboard and reports

The dashboard summarizes active product count, total inventory value, low-stock count, near-expiry batches, category stock distribution, recent activity, and a short expiry watchlist. Reports include:

- low-stock items and their stock value, optionally filtered by category;
- expired and upcoming batches within a requested 1–90 day expiry window; and
- activity totals grouped by user, role, and action, optionally filtered by date.

These pages are Inertia responses. Assert the user-visible page/props and authorization behavior, not SQL implementation details.

## Importing the legacy database

`inventory_system.sql` is preserved as a reference dump of the original application. It is **not** a replacement for Laravel migrations. The dump creates the legacy `roles`, `users`, `categories`, `items`, `batches`, and `activity_log` tables, but it does not contain Laravel's `sessions`, cache, jobs, or `notifications` tables.

Choose one of these workflows; do not combine the “create everything” and “import the full dump” steps blindly.

### Laravel-first conversion (recommended)

1. Start with an empty `inventory_system` database and run the complete Laravel schema and development seeders:

   ```bash
   php artisan migrate --seed
   ```

2. Do **not** import the dump's `CREATE TABLE` statements. Extract and transform compatible `INSERT` data only, loading parent tables before child tables: `roles`, `users`, `categories`, `items`, then `batches` and `activity_log`. Reconcile IDs, password hashes, and timestamps, and do not import a second copy of the seeded admin or roles.
3. Verify row counts, unique SKUs/category names, and foreign keys before using the converted data. Create notifications through the Laravel model/application or a reviewed data migration.

### Full-dump comparison or disposable import

For a disposable database, the full SQL dump can be imported for comparison, but a full `php artisan migrate` must not be run afterward: the dump already has tables and has no Laravel migration ledger, so the migration run will stop on existing tables. In that workflow, selectively apply only the missing Laravel infrastructure migrations (including sessions/cache/jobs and `notifications`) using a reviewed, disposable procedure, or import into a separate database and migrate a fresh copy. Never use this path for production without a tested backup and rollback plan.

The legacy schema also has two important conversion caveats:

1. **The legacy dump has no `notifications` table.** Importing it leaves the notification feature incomplete. The Laravel notifications migration must be applied before testing notifications or seeding notification rows.
2. **Archived SKUs and foreign keys can block imports/updates.** The legacy `items.is_deleted` flag is not Laravel's `deleted_at` soft-delete convention, and the unique SKU index still includes archived rows. Reusing a SKU from an archived item can therefore collide; the current public application has no restore route, so decide explicitly how archived SKUs will be retained or migrated. The current foreign-key actions are `items.category_id → categories` (`RESTRICT`), `batches.item_id → items` (`CASCADE`), `activity_log.user_id → users` (`SET NULL`), `activity_log.item_id → items` (`SET NULL`), and notification/session user references that cascade when a user is removed. (The legacy `users.role_id → roles` reference was migrated to Spatie's `model_has_roles` pivot during the RBAC conversion.) Load parent tables first and preserve these actions.

Keep the original dump backed up before testing destructive commands. For a repeatable local conversion, use a disposable database, verify counts and foreign keys, and keep the legacy SQL unchanged.

## Tests

The feature suite uses `RefreshDatabase` and an in-memory SQLite connection supplied by `phpunit.xml`; it does not require a developer MySQL server. Run it after creating `.env` and generating `APP_KEY` (or provide an equivalent test key in the test environment). It covers the public web contracts rather than controller internals:

- guest redirects and authenticated access (`AuthTest`);
- Fortify registration (including privilege-escalation attempts), login/logout, intended redirects, throttling, and password recovery with invalid/expired tokens (`FortifyAuthTest`);
- account profile/email updates and password changes, including legacy accounts without an email (`AccountManagementTest`);
- Spatie permission sets, Admin/Employee/User access matrices through direct requests, migrated assignments, and repeatable seeding (`RoleAccessTest`);
- item, category, and batch CRUD/validation, plus activity-log recording/indexing (`InventoryResourceTest`);
- administrator-only user management with last-admin/self-delete rules (`UserManagementTest`);
- notification ownership and read actions (`NotificationOwnershipTest`); and
- dashboard/report metrics (`ReportDashboardTest`).

Full verification results — including the live CSRF check, fresh-database setup, Pint, and the headless-browser suite in `scripts/browser_checks.py` — are recorded in [`docs/VERIFICATION.md`](docs/VERIFICATION.md).

Run all tests with:

```bash
php artisan test
```

Useful narrower commands are:

```bash
php artisan test --filter=Auth
php artisan test --filter=Inventory
php artisan test --filter=Reports
php vendor/bin/pint --test
```

These tests target the current Laravel routes and Inertia page contract. If a test fails because a route, migration, policy, or seeder is missing or incorrect, fix the application contract rather than weakening the assertion to a legacy `{success: ...}` AJAX response. On Windows, `vendor/bin/pint.bat --test` is the equivalent Pint command.

## Frontend build and development

The Inertia entry point and Blade shell live under `resources/js` and `resources/views`. Vite is the only frontend bundler:

```bash
npm run dev       # watch and hot reload during development
npm run build     # production asset build
```

Keep Laravel page names and the React `resources/js/Pages` names aligned. Controllers should return `Inertia::render(...)` responses; React receives serializable props. Do not expose the legacy `index.php?page=...` or `ajax/*.php` endpoints to the new React pages.

Useful diagnostics are:

```bash
php artisan about
php artisan route:list
php artisan config:clear
php artisan storage:link
```

## Preserved legacy files

The original application is retained for comparison and data-conversion work. It is not the preferred entry point for the Laravel application:

- `legacy/config/` contains the preserved database, seed, and session configuration notes;
- `inventory_system.sql` is the original MySQL dump;
- `index.php`, `ajax/`, `classes/`, `controllers/`, and `views/` are the preserved procedural PHP application and its AJAX endpoints; and
- the older `public/js/*` and `public/css/style.css` assets remain available for reference; they are separate from the current `public/index.php` entry point and Vite's generated `public/build` assets.

Changes to the Laravel application belong in Laravel's `app/`, `routes/`, `resources/`, and `database/` directories. Update the preserved legacy documentation rather than modifying the old runtime files as part of a Laravel feature.

## License

This project is released under the MIT License unless a repository-specific license is added by the project owner.
