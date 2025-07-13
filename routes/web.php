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

    /** ===============================
     *  HALAMAN UTAMA
     *  ============================== */
    $app->get('/', function (Request $request, Response $response) use ($container) {
        $view = $container->get('view');

        if (!isset($_SESSION['user'])) {
            return $view->render($response, 'home.twig', [
                'title' => 'Dashboard KM',
                'message' => 'Silakan login untuk melanjutkan.'
            ]);
        }

        $role = $_SESSION['user']['role'] ?? null;

        return match ($role) {
            'admin'        => $response->withHeader('Location', '/admin')->withStatus(302),
            'kontributor'  => $response->withHeader('Location', '/kontributor')->withStatus(302),
            'user'         => $response->withHeader('Location', '/pengguna')->withStatus(302),
            default        => $response->withHeader('Location', '/login')->withStatus(302),
        };
    });

    /** ===============================
     *  AUTH
     *  ============================== */
    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/logout', [AuthController::class, 'logout']);

    /** ===============================
     *  ADMIN
     *  ============================== */
    $app->get('/admin', [AdminController::class, 'index'])->add(RoleMiddleware::only('admin'));
    $app->get('/admin/users', [AdminController::class, 'listUsers'])->add(RoleMiddleware::only('admin'));
    $app->get('/admin/users/edit/{id}', [AdminController::class, 'editUser'])->add(RoleMiddleware::only('admin'));
    $app->post('/admin/users/edit/{id}', [AdminController::class, 'updateUser'])->add(RoleMiddleware::only('admin'));
    $app->get('/admin/delete/{id}', [AdminController::class, 'deleteUser'])->add(RoleMiddleware::only('admin'));

    $app->get('/admin/kategori-tag', [AdminController::class, 'manageCategoryTag'])->add(RoleMiddleware::only('admin'));
    $app->post('/admin/kategori-tag', [AdminController::class, 'storeCategoryTag'])->add(RoleMiddleware::only('admin'));

    $app->get('/admin/kategori/{id}/edit', [AdminController::class, 'editCategory'])->add(RoleMiddleware::only('admin'));
    $app->post('/admin/kategori/{id}/edit', [AdminController::class, 'updateCategory'])->add(RoleMiddleware::only('admin'));
    $app->get('/admin/kategori/{id}/delete', [AdminController::class, 'deleteCategory'])->add(RoleMiddleware::only('admin'));

    $app->get('/admin/tag/{id}/edit', [AdminController::class, 'editTag'])->add(RoleMiddleware::only('admin'));
    $app->post('/admin/tag/{id}/edit', [AdminController::class, 'updateTag'])->add(RoleMiddleware::only('admin'));
    $app->get('/admin/tag/{id}/delete', [AdminController::class, 'deleteTag'])->add(RoleMiddleware::only('admin'));

    $app->get('/admin/posts', [PostController::class, 'listAll'])->add(RoleMiddleware::only('admin'));
    $app->post('/admin/post/{id}/delete', [PostController::class, 'delete'])->add(RoleMiddleware::only('admin'));

    /** ===============================
     *  KONTRIBUTOR (tanpa group)
     *  ============================== */
    $app->get('/kontributor', function (Request $request, Response $response) use ($container) {
        $view = $container->get('view');
        return $view->render($response, 'kontributor.twig', [
            'title' => 'Halaman Kontributor'
        ]);
    })->add(RoleMiddleware::only('kontributor'));

    $app->get('/kontributor/postsaya', [PostController::class, 'index'])->add(RoleMiddleware::only('kontributor'));
    $app->get('/kontributor/post', [PostController::class, 'create'])->add(RoleMiddleware::only('kontributor'));
    $app->post('/kontributor/post', [PostController::class, 'store'])->add(RoleMiddleware::only('kontributor'));
    $app->get('/kontributor/post/{id}/edit', [PostController::class, 'edit'])->add(RoleMiddleware::only('kontributor'));
    $app->post('/kontributor/post/{id}/update', [PostController::class, 'update'])->add(RoleMiddleware::only('kontributor'));
    $app->post('/kontributor/post/{id}/delete', [PostController::class, 'delete'])->add(RoleMiddleware::only('kontributor'));

    /** ===============================
     *  PENGGUNA BIASA
     *  ============================== */
    $app->get('/pengguna', function (Request $request, Response $response) use ($container) {
        $view = $container->get('view');
        $db = $container->get('db');

        $posts = $db->select('posts', [
            '[>]categories' => ['category_id' => 'id'],
            '[>]users'      => ['user_id' => 'id']
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
            'title'   => 'Halaman Pengguna',
            'posts'   => $posts,
            'session'=> $_SESSION
        ]);
    })->add(RoleMiddleware::only(['admin', 'kontributor', 'user']));

    /** ===============================
     *  KOMENTAR & RATING
     *  ============================== */
    $app->post('/comments/add', [CommentController::class, 'add']);
    $app->post('/comments/{id}/delete', [CommentController::class, 'delete'])->setName('comment.delete');

    $app->post('/ratings/add', [RatingController::class, 'add'])
        ->add(RoleMiddleware::only(['admin', 'kontributor', 'user']));

    /** ===============================
     *  POST DETAIL (bisa dilihat semua)
     *  ============================== */
    $app->get('/story/{id}', [PostController::class, 'show']);
};
