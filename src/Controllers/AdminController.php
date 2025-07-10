<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AdminController
{
    private $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $_SESSION['user'] ?? null;

        return $this->view->render($response, 'admin/dashboard.twig', [
            'title' => 'Admin Dashboard',
            'user' => $user
        ]);
    }
}
