<?php
declare(strict_types=1);
/**
 * AutoPublisher X Enterprise - Front Controller
 * Initializes application, loads environment, config, and dispatches request.
 */

define('APX_START', microtime(true));
define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', __DIR__);

// Autoloader
require_once BASE_PATH . '/app/Core/Helpers.php';
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables from .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            putenv($line);
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Error reporting based on environment
$env = getenv('APP_ENV') ?: 'production';
if ($env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load configuration
\App\Core\Config::load(BASE_PATH . '/config');

// Boot application
$app = new \App\Core\App();
$app->boot();
$app->run();
