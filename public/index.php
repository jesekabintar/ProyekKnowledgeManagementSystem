<?php
session_start();

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Medoo\Medoo;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

// Twig
$container->set('view', function () {
    return Twig::create(__DIR__ . '/../src/Views', ['cache' => false]);
});

// Medoo
$container->set(Medoo::class, function () {
    return new Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'knowledge_system',
        'username' => 'root',
        'password' => ''
    ]);
});

// Controller DI binding
use Controllers\AuthController;
use Controllers\AdminController;
use Controllers\HomeController;

$container->set(AuthController::class, function ($c) {
    return new AuthController($c->get('view'), $c->get(Medoo::class));
});

$container->set(AdminController::class, function ($c) {
    return new AdminController($c->get('view'), $c->get(Medoo::class));
});

$container->set(HomeController::class, function ($c) {
    return new HomeController($c->get('view'));
});

$app = AppFactory::create();

// Middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::createFromContainer($app, 'view'));

// Load routes
(require __DIR__ . '/../routes/web.php')($app);

// Run
$app->run();
