<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DI\Container;
use App\Models\User;

class JWTMiddleware implements MiddlewareInterface
{
    private $container;
    private $protectedRoutes = [
        '/api/boards',
        '/api/tasks',
        '/api/users',
        '/api/notifications',
        '/api/messages',
        '/dashboard'
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();
        
        // Verificar si la ruta necesita autenticación
        if (!$this->needsAuthentication($uri)) {
            return $handler->handle($request);
        }

        $response = $this->container->get('view')->getEnvironment()->createTemplate('')
            ->renderBlock('json_response', []);

        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if (empty($authHeader)) {
                throw new \Exception('Token no proporcionado');
            }

            if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                throw new \Exception('Formato de token inválido');
            }

            $jwt = $matches[1];
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
            
            // Verificar que el usuario existe y está activo
            $user = User::find($decoded->user_id);
            if (!$user || !$user->is_active) {
                throw new \Exception('Usuario no válido');
            }

            // Agregar usuario al request
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('user_id', $user->id);
            
            return $handler->handle($request);

        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Token inválido: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }

    private function needsAuthentication(string $uri): bool
    {
        // Rutas públicas
        $publicRoutes = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/citizen-requests',
            '/login',
            '/register',
            '/citizen-portal',
            '/assets',
            '/uploads'
        ];

        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return false;
            }
        }

        return true;
    }
}