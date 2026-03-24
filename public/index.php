<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Resolve the Laravel app root (clinforce-ai/) relative to this file's location.
// When deployed: this file lives in public_html/, Laravel app is at ../clinforce-ai/
// When local:    this file lives in public/, Laravel app is at ../  (standard)
$laravelBase = is_dir(__DIR__ . '/../clinforce-ai')
    ? __DIR__ . '/../clinforce-ai'
    : __DIR__ . '/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $laravelBase . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $laravelBase . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $laravelBase . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
