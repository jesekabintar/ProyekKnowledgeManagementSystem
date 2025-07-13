<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Medoo\Medoo;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

// Twig: gunakan satu kali saja
$container->set(Twig::class, function () {
    $twig = Twig::create(__DIR__ . '/../src/Views', ['cache' => false]);
    $twig->getEnvironment()->addGlobal('session', $_SESSION);
    return $twig;
});
$container->set('view', fn($c) => $c->get(Twig::class));

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
$container->set('db', fn($c) => $c->get(Medoo::class));


// Controller DI binding
use Controllers\AuthController;
use Controllers\AdminController;
use Controllers\HomeController;
use Controllers\PostController;

$container->set(AuthController::class, fn($c) =>
    new AuthController($c->get(Twig::class), $c->get(Medoo::class))
);

$container->set(AdminController::class, fn($c) =>
    new AdminController($c->get(Twig::class), $c->get(Medoo::class))
);

$container->set(HomeController::class, fn($c) =>
    new HomeController($c->get(Twig::class), $c->get(Medoo::class))
);


$container->set(PostController::class, fn($c) =>
    new PostController($c->get(Twig::class), $c->get(Medoo::class))
);

// App & Middleware
$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::createFromContainer($app, Twig::class));

// Load routes
(require __DIR__ . '/../routes/web.php')($app);


foreach ($app->getRouteCollector()->getRoutes() as $route) {
    error_log('[ROUTE] ' . implode('|', $route->getMethods()) . ' ' . $route->getPattern());
}

// Run
$app->run();
