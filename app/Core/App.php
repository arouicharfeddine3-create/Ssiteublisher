<?php
namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\AdminMiddleware;
use App\Database\Migrations;
use App\Security\Csrf;
use App\Security\RateLimit;

class App
{
    private static ?self $instance = null;
    private Router $router;
    private Database $db;
    private array $middleware = [];

    public function __construct()
    {
        self::$instance = $this;
        $this->router = new Router();
        $this->db = Database::getInstance();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function boot(): void
    {
        // Start session if not CLI
        if (php_sapi_name() !== 'cli') {
            session_start();
            // CSRF token generation
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }

        if (Config::get('database.auto_migrate', true)) {
            (new Migrations())->run();
        }

        // Load routes
        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/api.php';
        require BASE_PATH . '/routes/admin.php';
        require BASE_PATH . '/routes/cron.php';

        // Register global middleware
        $this->middleware[] = new TenantMiddleware();
        // Apply middleware based on route prefix (done in Router)
    }

    public function run(): void
    {
        // Rate limiting on all requests
        $rateLimit = new RateLimit();
        if (!$rateLimit->check($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 100, 60)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests.']);
            exit;
        }

        foreach ($this->middleware as $middleware) {
            if (!$middleware->handle()) {
                return;
            }
        }

        // Dispatch request
        $this->router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/');
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getDb(): Database
    {
        return $this->db;
    }
}