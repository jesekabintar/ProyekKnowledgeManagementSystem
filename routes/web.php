<?php
use Slim\App;
use Slim\Views\Twig;
use Middleware\RoleMiddleware;
use Controllers\AuthController;
use Controllers\HomeController;
use Controllers\PostController;
use Controllers\AdminController;
use Controllers\RatingController;
use Controllers\CommentController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


return function (App $app) {
    $container = $app->getContainer();

    /**
     * HALAMAN UTAMA /
     */
    $app->get('/', function (Request $request, Response $response) use ($container) {
        $view = $container->get('view');

        // Log role (debugging)
        error_log('ROLE: ' . ($_SESSION['user']['role'] ?? 'none'));

        // Belum login
        if (!isset($_SESSION['user'])) {
            return $view->render($response, 'home.twig', [
                'title' => 'Dashboard KM',
                'message' => 'Silakan login untuk melanjutkan.'
            ]);
        }

        // Sudah login: redirect sesuai role
        $user = $_SESSION['user'];
        $role = $user['role'] ?? null;

        if ($role === 'admin') {
            return $response->withHeader('Location', '/admin')->withStatus(302);
        } elseif ($role === 'kontributor') {
            return $response->withHeader('Location', '/kontributor')->withStatus(302);
        } elseif ($role === 'user') {
            return $response->withHeader('Location', '/pengguna')->withStatus(302);
        }

        // Role tidak dikenali
        return $response->withHeader('Location', '/login')->withStatus(302);
    });

    /**
     * AUTH
     */
    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/logout', [AuthController::class, 'logout']);

    /**
     * ADMIN
     */
    $app->get('/admin', [AdminController::class, 'index'])
        ->add(RoleMiddleware::only('admin'));
    
    $app->get('/admin/delete/{id}', [AdminController::class, 'deleteUser'])
        ->add(RoleMiddleware::only('admin'));

    /**
     * KONTRIBUTOR DASHBOARD
     */
    $app->get('/kontributor', function (Request $request, Response $response) use ($container) {
        $view = $container->get('view');
        return $view->render($response, 'kontributor.twig', [
            'title' => 'Halaman Kontributor'
        ]);
    })->add(RoleMiddleware::only('kontributor'));

    /**
     * KONTRIBUTOR POST CRUD
     */
    $app->get('/kontributor/postsaya', [PostController::class, 'index'])
        ->add(RoleMiddleware::only('kontributor'));

    $app->get('/kontributor/post', [PostController::class, 'create'])
        ->add(RoleMiddleware::only('kontributor'));

    $app->post('/kontributor/post', [PostController::class, 'store'])
        ->add(RoleMiddleware::only('kontributor'));

    $app->get('/kontributor/post/{id}/edit', [PostController::class, 'edit'])
    ->add(RoleMiddleware::only('kontributor'));

$app->post('/kontributor/post/{id}/update', [PostController::class, 'update'])
    ->add(RoleMiddleware::only('kontributor'));

$app->post('/kontributor/post/{id}/delete', [PostController::class, 'delete'])
    ->add(RoleMiddleware::only('kontributor'));


    /**
     * PENGGUNA BIASA
     */
    $app->get('/pengguna', function (Request $request, Response $response) use ($container) {
    $view = $container->get('view');
    $db = $container->get('db');

    $posts = $db->select('posts', [
        '[>]categories' => ['category_id' => 'id'],
        '[>]users' => ['user_id' => 'id']
    ], [
        'posts.id',
        'posts.title',
        'posts.content',
        'posts.created_at',
        'categories.name(category)',
        'users.username'
    ], [
        'ORDER' => ['posts.created_at' => 'DESC']
    ]);

    return $view->render($response, 'pengguna.twig', [
        'title' => 'Halaman Pengguna',
        'posts' => $posts,
        'session' => $_SESSION
    ]);
})->add(RoleMiddleware::only(['admin', 'kontributor', 'user']));


    // $app->get('/story/{id}', [HomeController::class, 'showPost']);
$app->post('/comments/add', [CommentController::class, 'add']);

// routes
$app->post('/ratings/add', [RatingController::class, 'add'])
    ->add(RoleMiddleware::only(['user','kontributor','admin']));

    $app->get('/story/{id}', [PostController::class, 'show']);

// pakai POST supaya aman dari pre‑fetch GET
$app->post('/comments/{id}/delete', [CommentController::class, 'delete'])
     ->setName('comment.delete');



};
