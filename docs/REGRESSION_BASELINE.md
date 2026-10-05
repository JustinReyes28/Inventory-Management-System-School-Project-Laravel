# Regression Baseline (pre-conversion)

Recorded: 2026-10-05 — before integrating Fortify and Spatie.
Purpose: Phase 1 of the final-project plan requires the current state to be
recoverable and the passing results recorded before any change.

## Environment

| Component | Version |
| --- | --- |
| Laravel Framework | 13.33.0 |
| PHP | 8.5.11 (ZTS) |
| Composer | 2.10.3 |
| Node.js | 25.2.1 |
| npm | 11.6.2 |
| Database | SQLite (`database/database.sqlite`) |
| Mail | `log` mailer |

## Backups

`backups/20261005-130359/` (outside the Git repository) holds:

- `database.sqlite` — development database copy
- `inventory_system.sql` — phpMyAdmin schema/data dump
- `composer.lock`, `package-lock.json` — dependency lockfiles at baseline

## Test baseline

`php artisan test` → **21 passed (243 assertions)**

- `Tests\Feature\AuthTest` (5 tests) — guest redirects, login page, login/logout
- `Tests\Feature\ExampleTest` (1 test)
- `Tests\Feature\InventoryResourceTest` (7 tests) — categories/items/batches CRUD
- `Tests\Feature\NotificationOwnershipTest` (2 tests) — notification ownership
- `Tests\Feature\ReportDashboardTest` (2 tests) — dashboard/reports data
- `Tests\Feature\UserManagementTest` (3 tests) — admin-only user management
- `Tests\Unit\ExampleTest` (1 test)

## Build baseline

`npm run build` → success (Vite 7, `public/build/` emitted without errors).

## Route baseline (45 routes)

| Method | URI | Name | Action |
| --- | --- | --- | --- |
| ANY | `/` | `home` | Redirect to `/dashboard` |
| GET/POST | `/login` | `login`, `login.store` | `Auth\AuthenticatedSessionController` |
| POST | `/logout` | `logout` | `Auth\AuthenticatedSessionController@destroy` |
| GET | `/dashboard` | `dashboard` | `DashboardController@index` |
| resource | `categories` | `categories.*` | `CategoryController` (7 CRUD routes) |
| resource | `items` | `items.*` | `ItemController` (7 CRUD routes) |
| PATCH | `/items/{item}/archive` | `items.archive` | `ItemController@archive` |
| resource | `batches` | `batches.*` | `BatchController` (7 CRUD routes) |
| GET | `/activity-logs` | `activity-logs.index` | `ActivityLogController@index` |
| resource | `users` | `users.*` | `UserController` (7 CRUD routes, `can:manage-users`) |
| GET | `/reports` | `reports.index` | `ReportController@index` |
| GET | `/notifications` | `notifications.index` | `NotificationController@index` |
| GET | `/notifications/recent` | `notifications.recent` | `NotificationController@recent` |
| POST/PATCH | `/notifications/read-all` | `notifications.read-all` | `NotificationController@readAll` |
| POST/PATCH | `/notifications/{notification}/read` | `notifications.read` | `NotificationController@read` |

Framework-registered: `_inertia/devtools/*`, `storage/{path}`, `up`.

All named routes and public URLs above must remain identical after the
Fortify/Spatie conversion. Only `login`, `login.store`, and `logout` may change
ownership (they move to Fortify endpoints with the same URLs and names).

## Known baseline issues (to fix in later phases)

- Repository-wide `vendor/bin/pint --test` fails on preserved legacy PHP files
  (`ajax/`, `api/`, `classes/`, `controllers/`, `legacy/`, `views/`,
  `vercel-static/`, root `index.php`); active Laravel code is expected to pass.
- Registration and password-reset routes/pages do not exist yet.
- Roles use `users.role_id` + custom gates; Spatie is not installed.
- Authentication is a custom `AuthenticatedSessionController`; Fortify is absent.
- `resources/views/app.blade.php` uses a hand-rolled React refresh preamble.
- `resources/js/app.jsx` does not use `resolvePageComponent`.
- Browser console/responsive behavior not yet verified.
