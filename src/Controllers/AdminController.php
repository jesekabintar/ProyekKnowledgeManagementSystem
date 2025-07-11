<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AdminController
{
    private $view;
    private $db;

    public function __construct(Twig $view,  \Medoo\Medoo $db)
    {
        $this->view = $view;
         $this->db = $db;
    }

    public function index(Request $request, Response $response): Response
{
    $user = $_SESSION['user'] ?? null;

    $users = $this->db->select("users", "*", [
        "deleted_at" => null
    ]);

    return $this->view->render($response, 'admin/dashboard.twig', [
        'title' => 'Admin Dashboard',
        'user' => $user,
        'users' => $users
    ]);
}


    public function deleteUser(Request $request, Response $response, array $args)
{
    $id = $args['id'];

    $this->db->update("users", [
        "deleted_at" => date("Y-m-d H:i:s")
    ], [
        "id" => $id
    ]);

    return $response->withHeader('Location', '/admin')->withStatus(302);
}

}
