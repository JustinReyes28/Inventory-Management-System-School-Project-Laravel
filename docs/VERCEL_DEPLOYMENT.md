# Deploying the Laravel/Inertia app to Vercel

This repository uses Laravel 13 for routing, authentication, and database access. React is rendered through Inertia. The Vercel configuration runs Laravel in the community `vercel-php` function and publishes the Vite assets separately. The PHP runtime is community maintained; Vercel does not provide an official PHP runtime.

## Before importing the repository

1. Commit and push the Laravel application, including `api/index.php`, `vercel.json`, `composer.json`, `package.json`, `package-lock.json`, `resources/`, `app/`, `bootstrap/`, `config/`, `database/`, and `routes/`. The local `.env` file must stay out of Git.
2. Provision a persistent MySQL or MariaDB database that Vercel Functions can reach. A database running on your own computer or in XAMPP cannot serve the deployed app.
3. Run the Laravel migrations against that database from a PHP environment with the production `DB_*` settings:

   ```bash
   php artisan migrate --force
   ```

4. Create the initial administrator only after setting a strong `ADMIN_PASSWORD` and, if desired, `ADMIN_USERNAME` in that same secure environment:

   ```bash
   php artisan db:seed --force
   ```

   The seeder rejects a missing `ADMIN_PASSWORD` when `APP_ENV=production`. Do not use the local development password for a public deployment. Do not run migrations or seeders in Vercel's build command; preview and production builds could then change the same database.

## Schema and mail configuration after the Fortify/Spatie conversion

The schema now also contains Spatie's `permissions`, `model_has_roles`,
`model_has_permissions`, and `role_has_permissions` tables, a nullable unique
`users.email` column, `password_reset_tokens`, and `created_at`/`updated_at`
columns across the domain tables. Run `php artisan migrate --force` against
the hosted database and then `php artisan db:seed --class=RolesAndPermissionsSeeder --force`
(or the full `db:seed`, which also provisions the initial admin). Existing
rows migrate in place: legacy `users.role_id` assignments are backfilled into
`model_has_roles` before the legacy columns are dropped.

Password recovery needs a working mail transport. Set `MAIL_MAILER` (for
example `smtp` with `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_FROM_ADDRESS`) in Vercel's environment variables; the local `log` mailer
only writes to the function's logs and delivers nothing. `password_reset_tokens`
must exist (created by the migrations) or reset requests will fail.

## Vercel project settings

Import the Git repository in Vercel. Use this repository as the Root Directory and Node.js 22.x. `vercel.json` selects the **Other** framework preset, runs `npm run build:vercel`, and serves `vercel-static` as the static output. The PHP function runs on `vercel-php@0.9.0` (PHP 8.5). The function build also runs `npm ci` and `npm run build` through Composer's `vercel` script so Laravel can read its Vite manifest.

Add these environment variables in Vercel Project Settings for Production (and Preview if previews need a working database):

| Variable | Value |
| --- | --- |
| `APP_NAME` | `Inventory Management System` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate once with `php artisan key:generate --show`; retain the same key across deployments |
| `APP_URL` | The deployment's HTTPS URL or your custom production domain |
| `DB_CONNECTION` | `mysql` (or `mariadb` if your provider requires it) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Credentials for the hosted database |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `LOG_CHANNEL` | `stderr` |
| `APP_MAINTENANCE_DRIVER` | `cache` |

The `sessions` and `cache` tables are created by the repository's migrations. Use a separate database for Preview deployments if you enable them. Keep the database, `APP_KEY`, and administrator credentials outside the repository. Do not set `ASSET_URL` unless the static assets are moved to another domain.

Vercel Functions have a read-only deployment filesystem. `api/index.php` puts Laravel's compiled views and runtime cache in `/tmp`; the application uses the database for persistent sessions and cache. The current app does not store uploaded files. If file uploads are added, use external object storage rather than the local filesystem. Vercel does not run a persistent Laravel queue worker, so `QUEUE_CONNECTION=sync` runs any queued work during the request.

## After deployment

Open `/up`, then `/login`. Confirm that `/build/assets/...` files load and that signing in reaches `/dashboard`. If the first request reports missing database tables, apply the migrations to the database configured in Vercel. If it reports `Vite manifest not found`, check both the top-level Vercel build and the PHP function build logs.

To prepare the static output locally, run `npm run build:vercel`. This creates `vercel-static/`, which is generated and ignored by Git. A local build cannot prove that Vercel's PHP runtime and the hosted database are reachable; use the first Preview deployment to confirm those integrations before promoting to Production.
