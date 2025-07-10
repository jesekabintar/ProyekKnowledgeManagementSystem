<?php
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Medoo\Medoo;
use Slim\Views\Twig;

class AuthController
{
    private Twig $view;
    private Medoo $db;

    public function __construct(Twig $view, Medoo $db)
    {
        $this->view = $view;
        $this->db = $db;
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        $user = $this->db->get("users", "*", [
            "username" => $data['username'],
            "password" => md5($data['password']) // gunakan password_hash di produksi
        ]);

        if ($user) {
            $_SESSION['user'] = $user;
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $this->view->render($response, 'auth/login.twig', [
            'error' => 'Username atau password salah!'
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
