<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

// Shared helpers for the local CLI scripts. They work without vendor/ installed.

function localRoot(): string
{
    $root = dirname(__DIR__);
    if (! chdir($root)) {
        throw new RuntimeException('Cannot open the project directory.');
    }

    return $root;
}

function localCommand(array $arguments): string
{
    $executable = array_shift($arguments);
    if (! in_array($executable, ['composer', 'node', 'npm'], true)) {
        throw new RuntimeException('Unsupported shell executable.');
    }

    // Only fixed tool names reach the shell; quote every argument. PHP
    // itself is launched directly below, including paths with spaces.
    return $executable.' '.implode(' ', array_map('escapeshellarg', $arguments));
}

function localRun(array $arguments, bool $allowFailure = false): int
{
    fwrite(STDOUT, PHP_EOL.'> '.implode(' ', $arguments).PHP_EOL);
    $command = $arguments[0] === PHP_BINARY ? $arguments : localCommand($arguments);
    // Relay a single output pipe: inherited output handles can disappear
    // between nested Composer commands on Windows. Merging stderr avoids
    // a second pipe filling up while the first one is being read.
    $process = proc_open($command, [0 => STDIN, 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Cannot start the command above.');
    }
    stream_copy_to_stream($pipes[1], STDOUT);
    fclose($pipes[1]);
    $status = proc_close($process);
    if ($status !== 0 && ! $allowFailure) {
        throw new RuntimeException('Command failed. Resolve the error above, then rerun setup.');
    }

    return $status;
}

function localVersion(string $command): string
{
    exec(localCommand([$command, '--version']).' 2>&1', $output, $status);
    if ($status !== 0) {
        throw new RuntimeException("Cannot run {$command}. Install it, add it to PATH, and reopen the terminal. See docs/GROUPMATE_SETUP.md.");
    }

    return implode(PHP_EOL, $output);
}

function localCheckPhp(string $root): void
{
    if (PHP_VERSION_ID < 80401) {
        throw new RuntimeException('The committed lockfile requires PHP 8.4.1+. Running PHP '.PHP_VERSION.'.');
    }

    $lock = json_decode(file_get_contents($root.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
    $extensions = ['pdo', 'pdo_sqlite'];
    foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
        foreach (array_keys($package['require'] ?? []) as $requirement) {
            if (str_starts_with($requirement, 'ext-')) {
                $extensions[] = substr($requirement, 4);
            }
        }
    }

    $missing = array_filter(array_unique($extensions), fn ($extension) => ! extension_loaded($extension));
    if ($missing !== []) {
        throw new RuntimeException('Enable PHP extensions: '.implode(', ', $missing).'. Check php --ini, edit that php.ini, then rerun.');
    }
}

function localCheckTools(string $root): void
{
    localCheckPhp($root);
    $composer = localVersion('composer');
    if (! preg_match('/Composer version (\d+\.\d+\.\d+)/', $composer, $match) || version_compare($match[1], '2.0.0', '<')) {
        throw new RuntimeException('Composer 2 is required.');
    }

    $node = trim(localVersion('node'));
    $version = ltrim($node, 'v');
    $supported = (version_compare($version, '20.19.0', '>=') && version_compare($version, '21.0.0', '<'))
        || version_compare($version, '22.12.0', '>=');
    if (! $supported) {
        throw new RuntimeException('Node.js must satisfy ^20.19.0 or >=22.12.0. Install a supported Node.js LTS release.');
    }

    $npm = trim(localVersion('npm'));
    fwrite(STDOUT, 'PHP '.PHP_VERSION.'; '.$node.'; npm '.$npm.'; '.$composer.PHP_EOL);
}

function localBootstrap(string $root): Application
{
    require_once $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    if (! $app->environment('local')) {
        throw new RuntimeException('These scripts only run with APP_ENV=local. Existing environment settings were preserved.');
    }

    return $app;
}

function localDatabasePath(Application $app, string $root): ?string
{
    $connection = $app['db']->connection();
    $driver = $connection->getDriverName();
    if (! in_array($driver, ['sqlite', 'mysql', 'mariadb'], true)) {
        throw new RuntimeException('Local setup supports SQLite, MySQL, or MariaDB.');
    }

    if ($driver !== 'sqlite') {
        $host = $connection->getConfig('host');
        if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Local setup refuses remote databases. Configure a local DB_HOST first.');
        }
        if (! extension_loaded('pdo_mysql')) {
            throw new RuntimeException('Enable pdo_mysql in the PHP CLI php.ini for MySQL/MariaDB.');
        }

        return null;
    }

    $database = $connection->getConfig('database');
    if (! is_string($database) || $database === '' || $database === ':memory:' || str_starts_with($database, 'file:')) {
        throw new RuntimeException('Use a persistent SQLite file: DB_DATABASE=database/database.sqlite.');
    }

    $absolute = str_starts_with($database, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $database);
    $path = $absolute ? $database : $root.'/'.$database;
    $parent = realpath(dirname($path));
    $resolvedRoot = realpath($root).DIRECTORY_SEPARATOR;
    $insideRoot = $parent !== false && (PHP_OS_FAMILY === 'Windows'
        ? strncasecmp($parent.DIRECTORY_SEPARATOR, $resolvedRoot, strlen($resolvedRoot)) === 0
        : strncmp($parent.DIRECTORY_SEPARATOR, $resolvedRoot, strlen($resolvedRoot)) === 0);
    if (! $insideRoot) {
        throw new RuntimeException('The SQLite file must be inside this checkout. Existing DB_DATABASE was preserved; review it in .env.');
    }

    return $parent.DIRECTORY_SEPARATOR.basename($path);
}
