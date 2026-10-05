# Verification Record

## Groupmate setup verified on 2026-10-05

The Windows setup was exercised in a disposable copy containing only
Git-tracked files and the new setup files: no `.env`, `vendor/`, `node_modules/`,
SQLite database, built assets, or ignored runtime directories were copied.

- `setup.bat`: **PASS**. Created runtime directories before Composer's package
  discovery, installed locked dependencies, generated local SQLite settings/key,
  applied all 17 migrations, seeded four demo accounts and six inventory items,
  and built 661 React/Vite modules. MySQL and Vite servers were not needed.
- `composer setup` rerun: **PASS**, through the final completion message.
  Compared private hashes before/after: `.env` (including the key and an added
  comment), user attributes/password hashes, a custom category, items, and
  batches remained identical. Command output remains visible across nested
  Composer commands on Windows.
- `run.bat --port=8015`: **PASS**. The login page and its built React script
  both returned HTTP 200. The temporary server was stopped afterwards.
- Fresh-copy application tests: **48 PHP tests / 466 assertions** and **4
  JavaScript tests** passed.
- New PHP script syntax and Pint checks: **PASS**. Composer manifest validation
  passed with the existing exact-version constraint warnings.
- Local-only guards: a remote `DB_URL` and an outside-checkout SQLite path were
  refused before connecting; a fresh CLI process with `APP_ENV=production`
  was refused before starting a server.

Environment: Windows, PHP 8.5.11, Composer 2.10.3, Node 25.2.1, npm 11.6.2.
The current Composer lockfile requires PHP 8.4.1 or newer. macOS/Linux entry
points are provided but were not executed on those platforms. The optional
MySQL setup wrapper was not exercised here; migration/seeding compatibility
was verified separately below.

## Review corrections verified on 2026-10-05

The independent review found five defects missed by the initial checks below.
All five are now corrected:

| Finding | Correction and regression evidence |
| --- | --- |
| MySQL could not drop the legacy role index while its foreign key depended on it | Drop the foreign key before its index and column. Fresh setup and a populated legacy upgrade both pass on an isolated MySQL 8.0.44 instance. |
| Rerunning the seeder promoted a publicly registered account using `ADMIN_USERNAME` | Refuse existing non-admin accounts. Tests cover registration after an administrator renames their account, unchanged user attributes/roles, fresh provisioning, and repeated seeding of a legitimate Admin. |
| Account validation errors were flattened and invisible in React forms | Restore Inertia's standard shared errors, preserving named bags and errors during partial responses. Both profile and password response shapes have regression tests. |
| The PATCH archive endpoint accepted update permission while DELETE required delete permission | Both endpoints and the archive button require `delete items`. Direct-request tests verify update-only rejection and delete-only success for both endpoints. |
| The dashboard disclosed activity logs without `view activity logs` | Only query and share activity when authorized, and conditionally render the activity card/link. Tests verify the prop is absent without permission and present after an explicit grant. |

Browser verification also found a shared fallback helper passing an entire
array into `firstDefined`. This displayed valid quantities and dashboard
totals as zero. Numeric and text helpers now spread their fallback arguments;
four Node tests cover available fields, zero, negative values, invalid numbers,
and text defaults.

Current verification results:

- `php artisan test --compact`: **48 passed, 466 assertions**.
- `npm run test:unit`: **4 passed** (Node's built-in test runner).
- `npm run build`: **PASS**, 661 modules transformed.
- Active Laravel code Pint check: **PASS**.
- MySQL fresh migrations/full seeding/repeated seeding: **PASS** in a disposable
  database on port 33079. The test server was shut down and removed afterwards.
- MySQL upgrade from the original migrations with populated users, role IDs,
  categories and items: **PASS**. User IDs, names, passwords, creation dates,
  role assignments and stock were preserved. Reseeding also preserved the
  migrated administrator's profile and password.
- In-app Chromium with an isolated SQLite database and production assets:
  profile field errors appear, corrected input saves, activity appears for
  Employees and is absent for users without permission, update-only accounts
  see Edit without Archive, and delete-only accounts see Archive without Edit.
  The fixture displays 8 units, reorder threshold 2, 1 product and $80 stock
  value. No warning/error console messages were captured in these flows.

Screenshots: [account validation](screenshots/account-validation-review.jpg)
and [restricted dashboard with correct totals](screenshots/dashboard-permissions-review.jpg).
The earlier verification record below describes the original conversion;
its 22 browser checks are historical results, not a rerun of that script.

## Original conversion verification

Recorded: 2026-10-05. Covers Phase 5 of the final-project plan: security,
functionality, migration, build, formatting, and browser checks.

Environment: Laravel 13.33.0, PHP 8.5.11, Node 25.2.1, SQLite development
database (`database/database.sqlite`), `MAIL_MAILER=log`.

## Automated test suite

`php artisan test` → **40 passed (412 assertions)** (baseline before the
conversion: 21 passed / 243 assertions — see `docs/REGRESSION_BASELINE.md`).

Coverage added beyond the baseline:

| Area | Tests |
| --- | --- |
| Fortify registration | Creates a User-role account; submitted `role_id`/`role`/`roles`/`permissions`/`is_admin` fields are ignored (no escalation) |
| Fortify validation | Duplicate username/email, invalid email, short/mismatched password rejected with field errors |
| Login/logout | Intended-redirect honored, logout invalidates the session, protected pages redirect guests |
| Throttling | 6th login attempt inside a minute returns HTTP 429 |
| Password recovery | Reset email sent (notification asserted), reset via token works, unknown email / invalid token / expired token rejected, password unchanged on failure |
| Account page | Profile and email updates (the email-update path for legacy accounts), duplicate/invalid rejection, password change requires the current password, profile updates cannot escalate roles |
| Legacy compatibility | Accounts without an email can sign in with username/password and add an email afterwards |
| Spatie RBAC | Permission sets per role, Admin/Employee/User access matrices through direct requests, migrated assignments resolve through Spatie, repeated seeding is idempotent |
| CSRF wiring | Web middleware group runs Laravel CSRF protection (rejection itself verified against a live server below) |
| Baseline | Inventory CRUD/validation, activity logging, dashboard/report totals, notification ownership, last-admin/self-delete rules (kept passing) |

## CSRF rejection (live server, real HTTP)

Ordinary feature tests bypass CSRF, so token-less POSTs were sent against
`php artisan serve` with `curl`:

| Request | Result |
| --- | --- |
| `POST /login` without CSRF token | **419** |
| `POST /register` without CSRF token | **419** |
| `POST /forgot-password` without CSRF token | **419** |
| `GET /login` (ordinary request) | 200 |

## Additive migrations against an existing database

The full migration chain (roles→Spatie schema, permission tables, model
backfill, email column, password-reset tokens, domain timestamps, legacy
column drop) ran against a **copy of the populated development database**:

- 3 users, 3 role assignments preserved (`model_has_roles`: 1×Admin, 2×Employee)
- roles kept their IDs (1 = Admin, 2 = Employee; 3 = User appended)
- 20 permissions and 42 role-permission grants seeded
- `users.role_id` and `roles.role_name` dropped only after the backfill was
  verified (the backfill migration aborts if any assignment is missing)

## Fresh database setup

On a disposable database (`database/fresh_check.sqlite`):
`php artisan migrate --seed` succeeded end-to-end, and two further
`php artisan db:seed` runs left identical row counts (roles = 3,
permissions = 20, role_has_permissions = 42, users = 4, model_has_roles = 4).
This surfaced and fixed a real bug: seeding under `WithoutModelEvents`
suppresses Spatie's cache-flush events, so `RolesAndPermissionsSeeder` now
flushes the permission cache explicitly around its lookup phases.

Fresh-checkout installs were re-verified with `composer install` and
`npm ci` from the committed lockfiles.

## Route contract

`php artisan route:list` shows the baseline URLs and names unchanged, with
`login`, `login.store`, `logout`, `register`, `password.*`, and
`user-profile-information.update` / `user-password.update` owned by Fortify.
The custom authentication controller was removed, so there are no duplicate
authentication routes. Two-factor, passkey, and email-verification routes are
absent (those optional features stay disabled in `config/fortify.php`).

## Formatting

`vendor/bin/pint --test app bootstrap/app.php bootstrap/providers.php config
database routes tests` → **PASS (110 files)**. The repository-wide run is
scoped explicitly to active Laravel code; preserved legacy PHP (`ajax/`,
`api/`, `classes/`, `controllers/`, `legacy/`, `views/`, `vercel-static/`,
root `index.php`) and the generated `bootstrap/cache/*` are excluded.

## Builds and development mode

- `npm run build` → success (Vite 7).
- Development mode (`npm run dev` + `php artisan serve`, checked with
  `scripts/dev_mode_check.py`): `@viteReactRefresh`
  injects the React Fast Refresh preamble (replacing the hand-rolled one),
  `@vite/client` and `@react-refresh` load, the login page hydrates with no
  console or page errors. (One-time Vite dependency re-optimization on the
  first ever dev request can log transient hook warnings; clean afterwards.)

## Browser checks (headless Chromium, `scripts/browser_checks.py`)

22 checks, **all passing**, with **0 console errors, 0 page errors, 0
unexpected failed requests** (the deliberate `/users` 403 probes excepted):

- guest login page renders in the guest layout (no application navigation)
- registration validates (field errors beside inputs) and creates a User-role
  account whose navigation is view-only
- password recovery shows the sent status
- User role: view-only navigation, no mutation buttons, 403 on `/users`
- Employee role: inventory CRUD buttons, modal create flow shows server
  validation errors beside fields and creates data, 403 on `/users`
- Admin role: full navigation, user management page, account page
- logout returns to the login page
- mobile/tablet/desktop layouts render; the mobile navigation drawer opens

Screenshots for the presentation are in `docs/screenshots/`.

## Known issues fixed during verification

1. `app.jsx` attached the Inertia layout to the module instead of the page
   component, so pages rendered without the application shell. Fixed
   (Inertia v3 resolves `Component.layout`) and covered by the browser checks.
2. `RolesAndPermissionsSeeder` broke under `WithoutModelEvents` because
   Spatie's permission cache was never flushed mid-seed. Fixed with explicit
   cache flushes; seeding is now idempotent (verified above).
