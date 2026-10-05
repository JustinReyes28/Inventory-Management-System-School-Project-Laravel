# Run the project on a groupmate's computer

Cloning downloads the source. It does not include PHP/Node runtimes, installed
packages, `.env`, an application key, built assets, or a populated database.
Install the prerequisites once, then use `setup.bat` and `run.bat` on Windows.

The default local setup uses **SQLite**. You do not need MySQL, Apache, or XAMPP
for that option. Each groupmate gets their own local database: GitHub shares
the code, not your inventory records or accounts.

## Install the prerequisites once

| Software | Requirement | Official source |
| --- | --- | --- |
| PHP | **8.4.1+**; PHP 8.4/8.5 works with the current lockfile | [Windows installation](https://www.php.net/manual/en/install.windows.php), [Windows binaries](https://windows.php.net/download/) |
| Composer | Composer 2 | [Composer installer](https://getcomposer.org/download/) |
| Node.js + npm | A supported Node.js LTS release satisfying `^20.19.0` or `>=22.12.0` | [Node.js downloads](https://nodejs.org/en/download) |
| Git | Needed to clone and pull updates | [Git downloads](https://git-scm.com/install/) |

**PHP 8.3 is insufficient for the committed lockfile**: some locked Symfony
packages require 8.4.1+. Check an existing XAMPP PHP version before using it.

For standalone PHP on Windows, follow PHP's official instructions, extract it
into a folder such as `C:\php`, and add that folder to your user's `PATH`.
If needed, copy `php.ini-development` to `php.ini`. Set `extension_dir = "ext"`
and enable available extensions such as `mbstring`, `openssl`, `pdo_sqlite`,
`fileinfo`, `curl`, and `zip` by removing their leading semicolon. The script
lists any additional extensions required by the lockfile. Enable `pdo_mysql`
if choosing MySQL. Configure Composer to use that same PHP executable.

Install Node.js with npm and Git. Open a **new PowerShell window** after changing
`PATH`, then check:

```powershell
php --version
php --ini
composer --version
node --version
npm.cmd --version
git --version
```

`php --ini` identifies the configuration used by command-line PHP; Apache may
use a different file.

## Clone, set up, and run

In PowerShell, open the folder where you want to store the project:

```powershell
git clone https://github.com/JustinReyes28/Inventory-Management-System-School-Project-Laravel.git InventoryManagement
cd InventoryManagement
.\setup.bat
.\run.bat
```

Open **http://127.0.0.1:8000**. Keep the run terminal open; **Ctrl+C** stops the
app. Double-clicking the batch files also works once the tools are installed.
These files do not require PowerShell execution-policy changes.

The cloned repository is the application directory: it contains `artisan`,
`composer.json`, and `package.json`. You do not need the original author's
parent folder or an additional `proj_react` folder.

Setup needs internet access on first installation. It checks software, creates
a missing local `.env`, installs the **locked versions** with `composer install`
and `npm ci`, generates an application key when missing, creates a SQLite database,
runs additive migrations, seeds demo accounts/inventory, and builds React.
Fresh settings use `QUEUE_CONNECTION=sync`, so normal demonstrations need only
the one Laravel server terminal.

Reruns preserve existing `.env` settings, nonempty keys, inventory, and existing
administrator credentials. They never reset database tables. Demo inventory
is seeded only when the catalog is empty. An existing non-admin account using
`ADMIN_USERNAME` causes seeding to stop without promoting that account.
Seeding also reapplies the built-in role permissions and assigns demo roles.

```powershell
.\setup.bat --check       # Prerequisites only; change no project files
.\setup.bat --no-demo     # Skip sample inventory; still seed demo logins
.\run.bat --port=8001     # Use another port if 8000 is busy
```

For another port, open the printed URL. Generated links use that URL while the
server runs. Stop other Vite/`composer dev` processes in this checkout before
using `run.bat`: it selects built assets and removes `public/hot`, the generated
Vite development marker.

## Demo accounts and password recovery

Default credentials for a **fresh local database**:

| Role | Username | Password |
| --- | --- | --- |
| Admin | `admin` | `Admin@1234` |
| Employee | `employee` | `Employee@1234` |
| Employee | `employee2` | `Employee2@1234` |
| User, view only | `viewer` | `Viewer@1234` |

Admin can manage users. Employee can manage inventory and view activity logs.
User can view inventory/reports and manage their own notifications. Existing
passwords and `ADMIN_USERNAME`/`ADMIN_PASSWORD` overrides take precedence.
These demo credentials are intended for local demonstrations.

Password-reset emails use `MAIL_MAILER=log`: request a reset, then find its link
in `storage/logs/laravel.log`. An actual email inbox needs SMTP configuration.

## Update later

Stop the server and preserve your own code changes before pulling:

```powershell
git pull --ff-only
.\setup.bat
.\run.bat
```

This updates locked packages, applies pending migrations, and rebuilds assets.
Git will stop on conflicting changes. `.env`, application keys, and local
database files are ignored by Git; keep them on your own computer. Never use
`migrate:fresh` to update: it deletes tables and records.

## Optional local MySQL setup

If the group needs MySQL, install/start MySQL 8 or compatible MariaDB and create
an empty local database:

```sql
CREATE DATABASE inventory_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

On a fresh clone, copy `.env.example` to `.env` and configure it **before** setup:

```powershell
Copy-Item .env.example .env
```

```dotenv
APP_ENV=local
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=your-local-password
QUEUE_CONNECTION=sync
```

Enable `pdo_mysql`, then run `.\setup.bat --mysql` and `.\run.bat`. `--mysql`
retains MySQL defaults when creating a new `.env`; it does not overwrite
existing SQLite settings or create a MySQL database automatically.

Do not import `inventory_system.sql` first: that legacy dump lacks a Laravel
migration ledger and conflicts with the new setup. Local scripts refuse remote
databases and environments other than `APP_ENV=local`.

## Troubleshooting

| Problem | Action |
| --- | --- |
| Command not recognized | Install it, add it to `PATH`, reopen PowerShell, then use `setup.bat --check`. |
| PHP/platform or missing extension error | Use PHP 8.4.1+ and enable reported extensions in the CLI `php.ini`. Do not use `--ignore-platform-reqs`. |
| `npm.ps1 cannot be loaded` | Use `npm.cmd` for manual npm commands. The setup avoids PowerShell's npm shim. |
| Download fails | Check internet/proxy settings and rerun setup. The local database is preserved. |
| MySQL connection/unknown database | Start MySQL, create the database, and review your local `.env` credentials. |
| Admin username already belongs to a non-admin | Set `ADMIN_USERNAME` to the trusted administrator's current username, or an unused username for initial provisioning. |
| Port 8000 is busy | Stop the old server or use `run.bat --port=8001`. |
| Blank page/missing assets | Stop the server, rerun setup, then run `run.bat`. Open the Laravel URL, not Vite port 5173. |
| Old path after moving the folder | Use `DB_DATABASE=database/database.sqlite`, or correct your existing absolute database path. |

## macOS/Linux and development mode

With the same PHP, Composer, and Node/npm prerequisites:

```bash
php scripts/setup-local.php
php scripts/start-local.php
```

Composer shortcuts are `composer setup` and `composer start`. For frontend
development with hot reload, stop the built-asset server and run `composer dev`.
After editing React, rebuild with `npm.cmd run build` (Windows) or `npm run build`
before using the built-asset runner again.

Optional verification after setup:

```powershell
php artisan test --compact
npm.cmd run test:unit
```
