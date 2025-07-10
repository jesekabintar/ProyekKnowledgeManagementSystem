<?php
use Slim\App;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Controllers\AuthController;
use Controllers\AdminController;
use Middleware\RoleMiddleware;
use Controllers\HomeController;

return function (App $app) {

    $app->get('/', function (Request $request, Response $response) use ($app) {
        $view = $app->getContainer()->get('view');
        return $view->render($response, 'home.twig', ['title' => 'Dashboard KM']);
    });

    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/logout', [AuthController::class, 'logout']);

    $app->get('/admin', [AdminController::class, 'index'])
        ->add(RoleMiddleware::only('admin'));

    $app->get('/kontributor', function (Request $request, Response $response) use ($app) {
        $view = $app->getContainer()->get('view');
        return $view->render($response, 'kontributor.twig', [
            'title' => 'Halaman Kontributor'
        ]);
    })->add(RoleMiddleware::only('kontributor'));

    $app->get('/pengguna', function (Request $request, Response $response) use ($app) {
        $view = $app->getContainer()->get('view');
        return $view->render($response, 'pengguna.twig', [
            'title' => 'Halaman Pengguna'
        ]);
    })->add(RoleMiddleware::only(['admin', 'kontributor', 'pengguna']));
};
