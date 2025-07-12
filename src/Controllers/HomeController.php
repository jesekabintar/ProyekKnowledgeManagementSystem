<?php
namespace Controllers;

use Medoo\Medoo;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    private Twig $view;
    private Medoo $db;

    public function __construct(Twig $view, Medoo $db)
    {
        $this->view = $view;
        $this->db = $db; // ✅ INISIALISASI $db
    }

    public function index(Request $request, Response $response)
    {
        return $this->view->render($response, 'home.twig', [
            'title' => 'Dashboard KM'
        ]);
    }

    public function showPost(Request $request, Response $response, $args)
    {
        $postId = $args['id'];

        $post = $this->db->get('posts', '*', ['id' => $postId]);

        $comments = $this->db->select('comments', [
    '[>]users'    => ['user_id' => 'id'],
    '[>]ratings'  => [
        'user_id' => 'user_id',
        'post_id' => 'post_id'  // join on kedua kolom
    ]
], [
    'comments.comment',
    'comments.created_at',
    'users.username',
    'ratings.rating'  // <- ambil rating user utk post ini
], [
    'comments.post_id' => $postId,
    'ORDER'            => ['comments.created_at' => 'DESC']
]);


        return $this->view->render($response, 'layout/post.twig', [
            'post' => $post,
            'comments' => $comments,
            'session' => $_SESSION
        ]);

        $avgRating = $this->db->avg('ratings', 'rating', ['post_id' => $postId]);
$userRating = null;
if (isset($_SESSION['user'])) {
    $userRating = $this->db->get('ratings', 'rating', [
        'post_id' => $postId,
        'user_id' => $_SESSION['user']['id']
    ]);
}
return $this->view->render($response, 'layout/post.twig', [
    'post'        => $post,
    'comments'    => $comments,
    'avgRating'   => $avgRating ?: 0,
    'userRating'  => $userRating,
    'session'     => $_SESSION
]);

    }

    
}
