<?php

require __DIR__.'/local-common.php';

try {
    $root = localRoot();
    $port = 8000;
    foreach (array_slice($argv, 1) as $option) {
        if ($option === '--help') {
            fwrite(STDOUT, "Usage: php scripts/start-local.php [--port=8001]\nServes built assets locally. Keep this terminal open; Ctrl+C stops the app.\n");
            exit(0);
        }
        if (! preg_match('/^--port=(\d+)$/', $option, $match) || (int) $match[1] < 1024 || (int) $match[1] > 65535) {
            throw new RuntimeException('Use --port=8001, or another port between 1024 and 65535.');
        }
        $port = (int) $match[1];
    }
    localCheckPhp($root);
    foreach (['.env', 'vendor/autoload.php', 'public/build/manifest.json'] as $file) {
        if (! is_file($root.'/'.$file)) {
            throw new RuntimeException("Missing {$file}. Run setup.bat or php scripts/setup-local.php first.");
        }
    }

    localRun([PHP_BINARY, 'artisan', 'config:clear']);
    $app = localBootstrap($root);
    $databasePath = localDatabasePath($app, $root);
    if (! $app['config']->get('app.key') || ($databasePath !== null && ! is_file($databasePath))) {
        throw new RuntimeException('Local setup is incomplete. Run the setup script first.');
    }

    // Built assets are self-contained; remove the generated Vite hot marker
    // so the app does not request a development server on another port.
    if (is_file($root.'/public/hot') && ! unlink($root.'/public/hot')) {
        throw new RuntimeException('Cannot remove public/hot. Stop Vite and review folder permissions.');
    }
    $url = 'http://127.0.0.1:'.$port;
    putenv('APP_URL='.$url);
    fwrite(STDOUT, "\nOpen {$url} in your browser. Keep this terminal open; Ctrl+C stops the app.\nThis uses built assets. Stop any other Vite/composer dev process in this checkout first.\n");
    exit(localRun([PHP_BINARY, 'artisan', 'serve', '--host=127.0.0.1', '--port='.$port, '--tries=0'], allowFailure: true));
} catch (Throwable $error) {
    fwrite(STDERR, PHP_EOL.'Cannot start: '.$error->getMessage().PHP_EOL);
    exit(1);
}
