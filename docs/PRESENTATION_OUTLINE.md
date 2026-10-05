# Video Presentation Outline (3–7 minutes)

Covers the required topics from *Final_Project_Instructions_for_Video_Presentation*:
Authentication (Fortify), Role based access, CRUD, other functionalities, and
the code behind the Fortify integration, the Spatie integration, and the
React/Inertia setup with components.

Demo accounts (seeded, disposable):

| Role | Username | Password | Shows |
| --- | --- | --- | --- |
| Admin | `admin` | `Admin@1234` | Everything: user management, reports, activity logs |
| Employee | `employee` | `Employee@1234` | Inventory CRUD, reports, activity logs — no user management |
| User | `viewer` | `Viewer@1234` | View-only inventory and reports |

## 1. Intro + running app (≈30 s)

- One line: Laravel 13 + Inertia/React inventory system converted from the
  procedural PHP project — 7 interconnected tables, Eloquent, FormRequests.
- Show the dashboard logged in as admin (`docs/screenshots/09-admin-dashboard.png`
  is the fallback if the demo misbehaves).

## 2. Authentication — Fortify (≈1 min 30 s)

Functionality:
- Log out → guest layout → sign in with username/password (throttled 5/min).
- Registration page: create a new account (it lands as role **User**).
- Forgot password → the reset email is written to `storage/logs/laravel.log`
  (`MAIL_MAILER=log`) → copy the reset link → set a new password → sign in.

Code (switch to the editor):
- `config/fortify.php` — web guard, `username` login field, `/dashboard` home,
  `logout` redirect, features: registration, reset passwords, profile + password
  updates.
- `app/Providers/FortifyServiceProvider.php` — `Fortify::createUsersUsing(...)`
  and the other action bindings, plus the Inertia view callbacks
  (`Fortify::loginView(fn () => Inertia::render('Auth/Login'))`, etc.).
- `app/Actions/Fortify/CreateNewUser.php` — server-side validation, hashing into
  the legacy `password_hash` column, and the forced **User** role (`Arr::only`
  shows submitted role fields are ignored).
- `resources/js/Pages/Auth/Login.jsx` (and Register/ForgotPassword/
  ResetPassword) + `resources/js/Layouts/GuestLayout.jsx`.
- `resources/js/Pages/Account.jsx` — profile (the email-update path) + password
  forms via `PUT /user/profile-information` and `PUT /user/password`.

## 3. Role based access — Spatie (≈1 min 30 s)

Functionality:
- As **viewer**: nav hides *Activity logs* and *Users*; no Add/Edit/Delete
  buttons on items. Visiting `/users` directly → 403.
- As **employee**: full inventory CRUD; `/users` still 403.
- As **admin**: *Users* visible and manageable (show role select in the modal).

Code:
- `database/seeders/RolesAndPermissionsSeeder.php` — 20 granular permissions
  (view/create/update/delete for categories, items, batches, users + reports,
  activity logs, notifications) and the Admin/Employee/User policy.
- `routes/users.php` etc. — `->middleware('permission:delete users')` per action.
- `bootstrap/app.php` — `role`/`permission`/`role_or_permission` aliases.
- `app/Policies/UserPolicy.php` — `$user->can('view users')` + last-admin and
  self-delete safeguards.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares `auth.roles` and
  `auth.permissions`; `resources/js/Utils/auth.js` `useAuth()` powers the
  conditional rendering in `AppLayout.jsx` and the CRUD pages.
- Mention the migration story: `2026_10_05_000001_migrate_legacy_roles_to_spatie_schema`,
  `_000003_backfill_model_has_roles_from_legacy_role_id`, and the seeder that
  preserves role IDs 1/2.

## 4. CRUD (≈1 min)

Functionality (as admin or employee):
- Categories: create with a duplicate name → validation error beside the field
  → fix and save.
- Items: create/edit, low-stock badge, archive action.
- Batches: create with an invalid date/quantity → errors; then valid save.
- Point at the activity-log page afterwards (every mutation recorded).

Code:
- One resource controller (`app/Http/Controllers/ItemController.php`) with the
  full CRUD set returning `Inertia::render`, `app/Http/Requests/ItemRequest.php`
  for centralized validation, `app/Services/ItemService.php` for domain rules.
- `routes/items.php` — named resource routes split per file.

## 5. Other functionalities (≈30 s)

- Dashboard metrics + expiry watchlist; Reports (low stock, expiry window,
  activity summary).
- Notifications bell, read/read-all (ownership-scoped).
- Account page (profile/password), responsive layout + mobile drawer.

## 6. React/Inertia setup (≈30 s)

- `resources/views/app.blade.php` — `@viteReactRefresh`, `@vite`, `@inertiaHead`,
  `@inertia`, CSRF meta.
- `resources/js/app.jsx` — `createInertiaApp` + `resolvePageComponent` lazy
  loading; `Auth/*` pages keep the guest layout, everything else gets
  `AppLayout`.
- `resources/js/Components/` (FormField, Modal, UI) and `Pages/`.

Recording notes: shoot at 1280×800, keep the browser console open during the
CRUD and auth flows (it is clean), and keep the video inside 3–7 minutes.
Screenshots in `docs/screenshots/` are the fallback for any flow that fails
during recording.
