<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class JWTMiddleware implements MiddlewareInterface
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();
ECHO está desactivado.
        // Rutas que NO necesitan autenticación
        $publicRoutes = ['/api/health', '/login', '/citizen-portal', '/assets', '/uploads', '/'];
ECHO está desactivado.
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return $handler->handle($request);
            }
        }
ECHO está desactivado.
        return $handler->handle($request);
    }
}
