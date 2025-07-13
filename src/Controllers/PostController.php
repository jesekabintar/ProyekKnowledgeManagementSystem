<?php
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use Medoo\Medoo;

class PostController
{
    public function __construct(
        private Twig  $view,
        private Medoo $db,
    ) {}

    // Daftar semua posting milik kontributor
    public function index(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $posts = $this->db->select('posts', [
    '[>]categories' => ['category_id' => 'id'],
], [
    'posts.id',
    'posts.title',
    'posts.created_at',
    'posts.attachment',
    'categories.name(category)',
], [
    'posts.user_id' => $userId,
    'ORDER' => ['posts.created_at' => 'DESC'],
]);


        return $this->view->render($response, 'kontributor/postsaya.twig', compact('posts'));
    }

    // Form tambah posting
    public function create(Request $request, Response $response): Response
    {
        $categories = $this->db->select('categories', ['id', 'name']);
        return $this->view->render($response, 'kontributor/create.twig', compact('categories'));
    }

    // Simpan posting baru
    public function store(Request $request, Response $response): Response
{
    $data = (array) $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $filename = null;
    if (isset($uploadedFiles['attachment']) && $uploadedFiles['attachment']->getError() === UPLOAD_ERR_OK) {
        $file = $uploadedFiles['attachment'];
        $filename = uniqid() . '_' . $file->getClientFilename();
        $file->moveTo(__DIR__ . '/../../public/uploads/' . $filename);
    }

    $this->db->insert('posts', [
        'user_id'     => $userId,
        'category_id' => $data['category_id'],
        'title'       => $data['title'],
        'content'     => $data['content'],
        'attachment'  => $filename,
    ]);

    return $response->withHeader('Location', '/kontributor/postsaya')->withStatus(302);
}


    // Form edit posting
    public function edit(Request $request, Response $response, array $args): Response
    {
        $postId = $args['id'];
        $post = $this->db->get('posts', '*', ['id' => $postId]);

        if (!$post) {
            throw new HttpNotFoundException($request);
        }

        $categories = $this->db->select('categories', ['id', 'name']);
        return $this->view->render($response, 'kontributor/edit.twig', compact('post', 'categories'));
    }

    // Update posting
    public function update(Request $request, Response $response, array $args): Response
{
    $postId = $args['id'];
    $data = (array) $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    $updateData = [
        'title'   => $data['title'],
        'content' => $data['content'],
        'category_id' => $data['category_id'],
    ];

    if (isset($uploadedFiles['attachment']) && $uploadedFiles['attachment']->getError() === UPLOAD_ERR_OK) {
        $file = $uploadedFiles['attachment'];
        $filename = uniqid() . '_' . $file->getClientFilename();
        $file->moveTo(__DIR__ . '/../../public/uploads/' . $filename);
        $updateData['attachment'] = $filename;
    }

    $this->db->update('posts', $updateData, ['id' => $postId]);

    return $response->withHeader('Location', '/kontributor/postsaya')->withStatus(302);
}


    // Hapus posting (admin atau kontributor)
    public function delete(Request $request, Response $response, array $args): Response
    {
        $postId = $args['id'];
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $post = $this->db->get('posts', '*', ['id' => $postId]);
        if (!$post) {
            throw new HttpNotFoundException($request);
        }

        // Admin bisa hapus semua, kontributor hanya posting miliknya
        if ($user['role'] === 'admin' || $post['user_id'] == $user['id']) {
            $this->db->delete('posts', ['id' => $postId]);
        }

        $redirect = $user['role'] === 'admin' ? '/admin/posts' : '/kontributor/postsaya';
        return $response->withHeader('Location', $redirect)->withStatus(302);
    }

    // Menampilkan satu posting lengkap (untuk semua role)
    public function show(Request $request, Response $response, array $args): Response
    {
        $postId = $args['id'];
        $post = $this->db->get('posts', '*', ['id' => $postId]);

        if (!$post) {
            throw new HttpNotFoundException($request);
        }

        $comments = $this->db->select('comments', [
            '[>]users' => ['user_id' => 'id']
        ], [
            'comments.id',
            'comments.user_id',
            'comments.comment',
            'comments.rating',
            'comments.created_at',
            'users.username'
        ], [
            'comments.post_id' => $postId,
            'ORDER' => ['comments.created_at' => 'DESC'],
        ]);

        // Ambil rating milik user login jika ada
        $userId = $_SESSION['user']['id'] ?? null;
        $userRating = null;

        if ($userId) {
            $userRating = $this->db->get('comments', 'rating', [
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
        }

        return $this->view->render($response, 'layout/story.twig', [
            'post'       => $post,
            'comments'   => $comments,
            'userRating' => $userRating,
        ]);
    }

    // List semua posting (khusus admin)
    public function listAll(Request $request, Response $response): Response
    {
        $posts = $this->db->select('posts', [
            '[>]categories' => ['category_id' => 'id'],
            '[>]users'      => ['user_id' => 'id'],
        ], [
            'posts.id',
            'posts.title',
            'posts.created_at',
            'categories.name(category)',
            'users.username',
        ], [
            'ORDER' => ['posts.created_at' => 'DESC'],
        ]);

        return $this->view->render($response, 'admin/post_list.twig', compact('posts'));
    }

    
}
