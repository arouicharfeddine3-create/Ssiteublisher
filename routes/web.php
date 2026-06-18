<?php
use App\Core\Router;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Articles\ArticleController;
use App\Modules\Auth\AuthController;

$router = \App\Core\App::getInstance()->getRouter();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->group('/articles', function(Router $router) {
    $router->get('/', [ArticleController::class, 'index']);
    $router->get('/create', [ArticleController::class, 'create']);
    $router->post('/store', [ArticleController::class, 'store']);
    $router->get('/{id}/edit', [ArticleController::class, 'edit']);
    $router->put('/{id}', [ArticleController::class, 'update']);
    $router->delete('/{id}', [ArticleController::class, 'delete']);
});