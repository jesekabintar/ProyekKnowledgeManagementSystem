<?php
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Medoo\Medoo;
use Slim\Views\Twig;

class PostController
{
    private Twig $view;
    private Medoo $db;

    public function __construct(Twig $view, Medoo $db)
    {
        $this->view = $view;
        $this->db = $db;
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'];
        $posts = $this->db->select("posts", "*", ["user_id" => $userId]);

        return $this->view->render($response, "kontributor/postsaya.twig", [
            'posts' => $posts
        ]);
    }

   public function create(Request $request, Response $response): Response
{
    $categories = $this->db->select("categories", ["id", "name"]);

    return $this->view->render($response, "kontributor/create.twig", [
        'categories' => $categories
    ]);
}


    public function store(Request $request, Response $response): Response
{
    $data = (array)$request->getParsedBody();

    $this->db->insert("posts", [
        "user_id" => $_SESSION['user']['id'],
        "category_id" => $data['category_id'],
        "title" => $data['judul'],
        "content" => $data['isi']
    ]);

    return $response->withHeader('Location', '/kontributor/postsaya')->withStatus(302);
}


    public function edit(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $post = $this->db->get("posts", "*", ["id" => $id]);

        return $this->view->render($response, "kontributor/edit.twig", ['post' => $post]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $data = (array)$request->getParsedBody();

        $this->db->update("posts", [
            "judul" => $data['judul'],
            "isi" => $data['isi']
        ], ["id" => $id]);

        return $response->withHeader('Location', '/kontributor/postsaya')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $this->db->delete("posts", ["id" => $id]);

        return $response->withHeader('Location', '/kontributor/postsaya')->withStatus(302);
    }
}
