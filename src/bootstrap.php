<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno si existe el archivo
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Crear contenedor DI
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

// Crear aplicación Slim
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->setBasePath('/sistema-pablo/public');




// Middleware esencial
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// CORS básico
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

return $app;
