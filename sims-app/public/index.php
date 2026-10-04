<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Reset OPcache if running to ensure fresh bytecode and routes are loaded
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Auto-invalidate stale route cache if routes/web.php or routes/api.php was updated
$routeCache = __DIR__.'/../bootstrap/cache/routes-v7.php';
if (file_exists($routeCache)) {
    clearstatcache(true, $routeCache);
    $cacheMtime = @filemtime($routeCache) ?: 0;
    $webRoutes = __DIR__.'/../routes/web.php';
    $apiRoutes = __DIR__.'/../routes/api.php';
    if ((file_exists($webRoutes) && @filemtime($webRoutes) > $cacheMtime) ||
        (file_exists($apiRoutes) && @filemtime($apiRoutes) > $cacheMtime)) {
        @unlink($routeCache);
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
