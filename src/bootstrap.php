<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurar zona horaria
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Cordoba');

// Crear contenedor DI
$containerBuilder = new ContainerBuilder();

// Configurar servicios en el contenedor
$containerBuilder->addDefinitions([
    // Base de datos
    'db' => function () {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    },

    // Logger
    'logger' => function () {
        $logger = new Logger('municipal_system');
        $handler = new StreamHandler($_ENV['LOG_PATH'] . 'app.log', Logger::DEBUG);
        $logger->pushHandler($handler);
        return $logger;
    },

    // Twig
    'view' => function () {
        return \Slim\Views\Twig::create(__DIR__ . '/Views', [
            'cache' => $_ENV['APP_ENV'] === 'production' ? __DIR__ . '/../cache/twig' : false,
            'debug' => $_ENV['APP_DEBUG'] === 'true'
        ]);
    },

    // Mailer
    'mailer' => function () {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port = $_ENV['MAIL_PORT'];
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->CharSet = 'UTF-8';
        return $mail;
    }
]);

$container = $containerBuilder->build();

// Inicializar base de datos
$container->get('db');

// Crear aplicación Slim
AppFactory::setContainer($container);
$app = AppFactory::create();

// Agregar middleware de parsing del body
$app->addBodyParsingMiddleware();

// Middleware de CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Middleware de manejo de errores
$app->addErrorMiddleware($_ENV['APP_DEBUG'] === 'true', true, true);

// Middleware de autenticación JWT
$app->add(new App\Middleware\JWTMiddleware($container));

return $app;