<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application base path
|--------------------------------------------------------------------------
| Standard deploy: private app sits next to public/ => dirname(__DIR__).
| Split deploy (fixed docroot): private app lives elsewhere. Resolved via:
|   1. APP_BASE_PATH env var (SetEnv in docroot .htaccess, or PHP-FPM env), or
|   2. an .app-base-path file next to this index.php containing the path.
| Neither is readable from .env — this file runs before bootstrap.
*/
$appPath = getenv('APP_BASE_PATH') ?: dirname(__DIR__);
if ($appPath === dirname(__DIR__) && is_file($f = __DIR__.'/.app-base-path')) {
    $appPath = trim(file_get_contents($f)) ?: $appPath;
}

define('LARAVEL_START', microtime(true));

// Maintenance mode flag lives in the private app's storage.
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader.
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel.
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// Public assets physically live in the web root (this dir), which may differ
// from $appPath/public on hosts with a fixed docroot.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
