# Preserved legacy application

This directory contains the configuration notes kept from the original procedural PHP version of the inventory system. It is reference material for the Laravel 13 conversion, not a second application entry point.

## What is preserved

- `config/db.php` — the original `mysqli` connection helper, `inventory_system` constants, and historical database credentials. Do not deploy it or reuse those credentials.
- `config/seed.php` — the original role/admin seed behavior. It contains a historical development password and must not be deployed or used as a production secret.
- `config/session.php` — the original PHP session cookie settings and CSRF helpers.

The larger legacy runtime remains beside this directory in `../index.php`, `../ajax/`, `../classes/`, `../controllers/`, `../views/`, and the older `../public/` assets. Those files are kept so the data model and behavior can be compared during migration.

## Data mapping

| Legacy concept | Laravel migration/table | Notes |
| --- | --- | --- |
| `roles` | `roles` | `Admin` (1) and `Employee` (2) are the two application roles. |
| `users.full_name`, `username`, `password_hash`, `role_id` | `users` | Authentication is by username; passwords are hashed. |
| `categories.category_name` | `categories` | Names are unique. |
| `items` | `items` | SKU is unique; `is_deleted` is the archive flag. |
| `batches` | `batches` | Each batch belongs to an item and has an expiry date. |
| `activity_log` | `activity_log` | The table name intentionally stays singular. |
| Legacy notification code | `notifications` | This table is missing from the old SQL dump and must be created by Laravel migrations. |

## Import warnings

1. Import parent tables before child tables: roles, users, categories, and items precede batches and activity records.
2. The legacy SKU unique index still includes archived rows. Reusing a SKU after `is_deleted = 1` can fail until the conversion policy is chosen (restore the row, retain the SKU forever, or change the uniqueness strategy).
3. The legacy foreign keys use `RESTRICT` for user→role and item→category, `CASCADE` for batch→item, and `SET NULL` for activity actor/item references. The Laravel schema also cascades notification/session user references. Do not disable constraints or delete referenced rows casually; preserve these actions during conversion.
4. `inventory_system.sql` does not contain `notifications`, `sessions`, cache, or jobs tables. A successful import is not a complete Laravel schema, especially when the default database session/cache/queue drivers are enabled.
5. Keep a backup of the dump and use a disposable database while testing any import or `migrate:fresh` workflow.

## Relationship to the Laravel tests

The feature tests in `../tests/Feature` use the same legacy-compatible field names so they can test the conversion contract without loading the old runtime. They should be run against the Laravel application (`php artisan test`), not by including these files from a web request.
