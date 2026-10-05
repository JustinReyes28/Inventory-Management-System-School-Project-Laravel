<?php

// Vercel Functions can write only to /tmp. Keep Laravel's runtime files there.
$storagePath = '/tmp/laravel-storage';

foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir($storagePath.'/'.$directory)) {
        mkdir($storagePath.'/'.$directory, 0775, true);
    }
}

$runtimePaths = [
    'LARAVEL_STORAGE_PATH' => $storagePath,
    'APP_CONFIG_CACHE' => $storagePath.'/framework/cache/config.php',
    'APP_EVENTS_CACHE' => $storagePath.'/framework/cache/events.php',
    'APP_PACKAGES_CACHE' => $storagePath.'/framework/cache/packages.php',
    'APP_ROUTES_CACHE' => $storagePath.'/framework/cache/routes.php',
    'APP_SERVICES_CACHE' => $storagePath.'/framework/cache/services.php',
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
];

foreach ($runtimePaths as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require __DIR__.'/../public/index.php';
