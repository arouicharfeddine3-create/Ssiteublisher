<?php
$router = \App\Core\App::getInstance()->getRouter();

$router->group('/admin', function($router) {
    // Admin-only routes
    $router->get('/scraper', [\App\Modules\Scraper\ScraperController::class, 'sources'], [\App\Middleware\AdminMiddleware::class]);
    $router->get('/settings', [\App\Modules\Settings\SettingsController::class, 'index'], [\App\Middleware\AdminMiddleware::class]);
});