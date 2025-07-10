<?php 
namespace Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], $this->allowedRoles)) {
            $response = new SlimResponse();
            $response->getBody()->write("Akses ditolak. Halaman ini hanya untuk: " . implode(', ', $this->allowedRoles));
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }

    public static function only(string|array $roles): self
    {
        return new self((array)$roles);
    }
}
