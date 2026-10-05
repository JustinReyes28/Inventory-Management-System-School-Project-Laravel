<?php

use Illuminate\Database\QueryException;

require __DIR__.'/local-common.php';

try {
    $root = localRoot();
    $options = array_slice($argv, 1);
    foreach ($options as $option) {
        if (! in_array($option, ['--check', '--mysql', '--no-demo', '--help'], true)) {
            throw new RuntimeException("Unknown option: {$option}. Use --help.");
        }
    }
    if (in_array('--help', $options, true)) {
        fwrite(STDOUT, "Usage: php scripts/setup-local.php [--check] [--mysql] [--no-demo]\nFresh installs default to SQLite. Existing .env and database data are preserved.\n--check checks prerequisites without installing or changing files.\n--mysql keeps MySQL settings when creating .env; create the database first.\n--no-demo skips the sample inventory; demo login accounts are still seeded.\n");
        exit(0);
    }

    localCheckTools($root);
    if (in_array('--check', $options, true)) {
        fwrite(STDOUT, "Prerequisites passed.\n");
        exit(0);
    }

    // Git does not preserve empty/ignored runtime directories. Composer's
    // package discovery needs bootstrap/cache before dependencies are ready.
    foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/private', 'storage/app/public'] as $directory) {
        $path = $root.'/'.$directory;
        if ((! is_dir($path) && ! mkdir($path, 0755, true)) || ! is_writable($path)) {
            throw new RuntimeException("Cannot create/write {$directory}. Check folder permissions.");
        }
    }

    if (! file_exists($root.'/.env')) {
        $environment = file_get_contents($root.'/.env.example');
        $environment = str_replace('APP_URL=http://localhost:8000', 'APP_URL=http://127.0.0.1:8000', $environment);
        $environment = str_replace('QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=sync', $environment);
        if (! in_array('--mysql', $options, true)) {
            $environment = str_replace('DB_CONNECTION=mysql', 'DB_CONNECTION=sqlite', $environment);
            $environment = str_replace('DB_DATABASE=inventory_system', 'DB_DATABASE=database/database.sqlite', $environment);
        }
        if (file_put_contents($root.'/.env', $environment, LOCK_EX) === false) {
            throw new RuntimeException('Cannot create .env. Check folder permissions.');
        }
        fwrite(STDOUT, "Created local .env.\n");
    } else {
        fwrite(STDOUT, "Keeping your existing .env settings and application key.\n");
    }

    localRun(['composer', 'install', '--no-interaction', '--prefer-dist']);
    localRun(['composer', 'check-platform-reqs']);
    localRun([PHP_BINARY, 'artisan', 'config:clear']);
    $app = localBootstrap($root);
    $databasePath = localDatabasePath($app, $root);
    if (in_array('--mysql', $options, true) && $databasePath !== null) {
        throw new RuntimeException('--mysql does not overwrite an existing SQLite .env. Configure MySQL in .env first.');
    }
    if ($databasePath !== null && ! file_exists($databasePath) && ! touch($databasePath)) {
        throw new RuntimeException('Cannot create the local SQLite database.');
    }

    if (! $app['config']->get('app.key')) {
        localRun([PHP_BINARY, 'artisan', 'key:generate']);
    }

    try {
        $connection = $app['db']->connection();
        $schema = $connection->getSchemaBuilder();
        if ($schema->hasTable('users') && ! $schema->hasTable('migrations')) {
            throw new RuntimeException('This database contains a legacy import without a migration ledger. Use an empty local database; do not import inventory_system.sql for this setup.');
        }
    } catch (QueryException $error) {
        throw new RuntimeException('Cannot connect to the local database. Start MySQL if selected, create the database, and review DB_* in .env. The existing data was not reset.', previous: $error);
    }

    localRun([PHP_BINARY, 'artisan', 'migrate', '--force']);
    localRun([PHP_BINARY, 'artisan', 'db:seed', '--force']);
    if (! in_array('--no-demo', $options, true)) {
        localRun([PHP_BINARY, 'artisan', 'db:seed', '--class=DemoInventorySeeder', '--force']);
    }
    localRun(['npm', 'ci']);
    localRun(['npm', 'run', 'build']);

    fwrite(STDOUT, "\nSetup complete. Run run.bat (Windows) or php scripts/start-local.php.\nOpen http://127.0.0.1:8000.\nDefault local demo logins: admin / Admin@1234; employee / Employee@1234; viewer / Viewer@1234.\nExisting passwords and ADMIN_* overrides take precedence.\nSee docs/GROUPMATE_SETUP.md for updates, MySQL, password recovery, and troubleshooting.\n");
} catch (Throwable $error) {
    fwrite(STDERR, PHP_EOL.'Setup stopped: '.$error->getMessage().PHP_EOL);
    exit(1);
}
