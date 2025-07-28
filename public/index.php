<?php

// Mostrar errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir bootstrap
require __DIR__ . '/../src/bootstrap.php';

// Incluir rutas
require __DIR__ . '/../src/routes.php';

// Ejecutar aplicación
$app->run();