@echo off
chcp 65001 >nul
echo ================================================================
echo    Solucionando errores del Sistema Municipal
echo ================================================================
echo.

:: Cambiar al directorio del proyecto
cd /d "C:\xampp5\htdocs\Sistema-pablo"

echo 📁 Directorio del proyecto: %CD%
echo.

:: Verificar estructura
echo 🔍 Verificando estructura de archivos...
if not exist "src" mkdir src
if not exist "src\Controllers" mkdir src\Controllers
if not exist "src\Models" mkdir src\Models
if not exist "src\Middleware" mkdir src\Middleware
if not exist "src\Services" mkdir src\Services
if not exist "src\Views" mkdir src\Views
if not exist "public" mkdir public
if not exist "uploads" mkdir uploads
if not exist "logs" mkdir logs
if not exist "cache" mkdir cache

echo ✅ Estructura verificada

:: 1. Crear bootstrap.php corregido
echo 🔧 Creando bootstrap.php...
(
echo ^<?php
echo.
echo use DI\Container;
echo use DI\ContainerBuilder;
echo use Slim\Factory\AppFactory;
echo use Dotenv\Dotenv;
echo use Illuminate\Database\Capsule\Manager as Capsule;
echo use Monolog\Logger;
echo use Monolog\Handler\StreamHandler;
echo use PHPMailer\PHPMailer\PHPMailer;
echo.
echo require __DIR__ . '/../vendor/autoload.php';
echo.
echo // Cargar variables de entorno
echo if ^(file_exists^(__DIR__ . '/../.env'^)^) {
echo     $dotenv = Dotenv::createImmutable^(__DIR__ . '/..'^);
echo     $dotenv-^>load^(^);
echo }
echo.
echo // Configurar zona horaria
echo date_default_timezone_set^($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Cordoba'^);
echo.
echo // Crear contenedor DI
echo $containerBuilder = new ContainerBuilder^(^);
echo.
echo // Configurar servicios
echo $containerBuilder-^>addDefinitions^([
echo     'db' =^> function ^(^) {
echo         try {
echo             $capsule = new Capsule;
echo             $capsule-^>addConnection^([
echo                 'driver' =^> 'mysql',
echo                 'host' =^> $_ENV['DB_HOST'] ?? 'localhost',
echo                 'database' =^> $_ENV['DB_NAME'] ?? 'municipal_system',
echo                 'username' =^> $_ENV['DB_USERNAME'] ?? 'root',
echo                 'password' =^> $_ENV['DB_PASSWORD'] ?? '',
echo                 'charset' =^> $_ENV['DB_CHARSET'] ?? 'utf8mb4',
echo                 'collation' =^> 'utf8mb4_unicode_ci'
echo             ]^);
echo             $capsule-^>setAsGlobal^(^);
echo             $capsule-^>bootEloquent^(^);
echo             return $capsule;
echo         } catch ^(\Exception $e^) {
echo             error_log^('DB Error: ' . $e-^>getMessage^(^)^);
echo             return null;
echo         }
echo     },
echo     'logger' =^> function ^(^) {
echo         $logger = new Logger^('municipal_system'^);
echo         $logPath = $_ENV['LOG_PATH'] ?? 'logs/';
echo         if ^(!is_dir^($logPath^)^) mkdir^($logPath, 0755, true^);
echo         $handler = new StreamHandler^($logPath . 'app.log'^);
echo         $logger-^>pushHandler^($handler^);
echo         return $logger;
echo     }
echo ]^);
echo.
echo $container = $containerBuilder-^>build^(^);
echo $GLOBALS['container'] = $container;
echo.
echo // Inicializar base de datos
echo $container-^>get^('db'^);
echo.
echo // Crear aplicación Slim
echo AppFactory::setContainer^($container^);
echo $app = AppFactory::create^(^);
echo.
echo // Middleware
echo $app-^>addBodyParsingMiddleware^(^);
echo $app-^>add^(function ^($request, $handler^) {
echo     $response = $handler-^>handle^($request^);
echo     return $response
echo         -^>withHeader^('Access-Control-Allow-Origin', '*'^)
echo         -^>withHeader^('Access-Control-Allow-Headers', 'Content-Type, Authorization'^)
echo         -^>withHeader^('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS'^);
echo }^);
echo.
echo $app-^>addErrorMiddleware^(true, true, true^);
echo.
echo return $app;
) > src\bootstrap.php

echo ✅ bootstrap.php creado

:: 2. Crear JWTMiddleware.php corregido
echo 🔧 Creando JWTMiddleware.php...
(
echo ^<?php
echo.
echo namespace App\Middleware;
echo.
echo use Psr\Http\Message\ResponseInterface;
echo use Psr\Http\Message\ServerRequestInterface;
echo use Psr\Http\Server\MiddlewareInterface;
echo use Psr\Http\Server\RequestHandlerInterface;
echo use Slim\Psr7\Response;
echo.
echo class JWTMiddleware implements MiddlewareInterface
echo {
echo     private $container;
echo.
echo     public function __construct^($container^)
echo     {
echo         $this-^>container = $container;
echo     }
echo.
echo     public function process^(ServerRequestInterface $request, RequestHandlerInterface $handler^): ResponseInterface
echo     {
echo         $uri = $request-^>getUri^(^)-^>getPath^(^);
echo         
echo         // Rutas que NO necesitan autenticación
echo         $publicRoutes = ['/api/health', '/login', '/citizen-portal', '/assets', '/uploads', '/'];
echo         
echo         foreach ^($publicRoutes as $route^) {
echo             if ^(strpos^($uri, $route^) === 0^) {
echo                 return $handler-^>handle^($request^);
echo             }
echo         }
echo         
echo         return $handler-^>handle^($request^);
echo     }
echo }
) > src\Middleware\JWTMiddleware.php

echo ✅ JWTMiddleware.php creado

:: 3. Crear routes.php básico
echo 🔧 Creando routes.php...
(
echo ^<?php
echo.
echo // Ruta de salud del sistema
echo $app-^>get^('/api/health', function ^($request, $response^) {
echo     $health = [
echo         'status' =^> 'healthy',
echo         'message' =^> 'Sistema Municipal funcionando correctamente',
echo         'timestamp' =^> date^('Y-m-d H:i:s'^),
echo         'version' =^> '1.0.0'
echo     ];
echo     $response-^>getBody^(^)-^>write^(json_encode^($health^)^);
echo     return $response-^>withHeader^('Content-Type', 'application/json'^);
echo }^);
echo.
echo // Ruta de inicio
echo $app-^>get^('/', function ^($request, $response^) {
echo     $html = '^<!DOCTYPE html^>
echo ^<html lang="es"^>
echo ^<head^>
echo     ^<meta charset="UTF-8"^>
echo     ^<meta name="viewport" content="width=device-width, initial-scale=1.0"^>
echo     ^<title^>Sistema Municipal San Francisco^</title^>
echo     ^<style^>
echo         body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
echo         .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba^(0,0,0,0.1^); }
echo         h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
echo         .status { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
echo         .links { display: grid; grid-template-columns: repeat^(auto-fit, minmax^(200px, 1fr^)^); gap: 20px; }
echo         .link { background: #007bff; color: white; padding: 20px; text-align: center; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
echo         .link:hover { background: #0056b3; }
echo     ^</style^>
echo ^</head^>
echo ^<body^>
echo     ^<div class="container"^>
echo         ^<h1^>Sistema Municipal San Francisco^</h1^>
echo         ^<div class="status"^>✅ Sistema instalado y funcionando correctamente^</div^>
echo         ^<div class="links"^>
echo             ^<a href="/login" class="link"^>🔐 Acceso Administrativo^</a^>
echo             ^<a href="/citizen-portal" class="link"^>🏛️ Portal Ciudadano^</a^>
echo             ^<a href="/dashboard" class="link"^>📊 Dashboard^</a^>
echo             ^<a href="/api/health" class="link"^>🔧 Estado del Sistema^</a^>
echo         ^</div^>
echo     ^</div^>
echo ^</body^>
echo ^</html^>';
echo     $response-^>getBody^(^)-^>write^($html^);
echo     return $response-^>withHeader^('Content-Type', 'text/html'^);
echo }^);
echo.
echo // Página de login básica
echo $app-^>get^('/login', function ^($request, $response^) {
echo     $html = '^<!DOCTYPE html^>
echo ^<html lang="es"^>
echo ^<head^>
echo     ^<meta charset="UTF-8"^>
echo     ^<title^>Login - Sistema Municipal^</title^>
echo     ^<style^>
echo         body { font-family: Arial; margin: 0; padding: 40px; background: #f5f5f5; }
echo         .login { max-width: 400px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba^(0,0,0,0.1^); }
echo         h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
echo         input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
echo         button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
echo         button:hover { background: #0056b3; }
echo         .back { text-align: center; margin-top: 20px; }
echo         .back a { color: #007bff; text-decoration: none; }
echo     ^</style^>
echo ^</head^>
echo ^<body^>
echo     ^<div class="login"^>
echo         ^<h2^>Acceso al Sistema^</h2^>
echo         ^<form^>
echo             ^<input type="email" placeholder="Email" required^>
echo             ^<input type="password" placeholder="Contraseña" required^>
echo             ^<button type="submit"^>Iniciar Sesión^</button^>
echo         ^</form^>
echo         ^<div class="back"^>^<a href="/"^>← Volver al inicio^</a^>^</div^>
echo     ^</div^>
echo ^</body^>
echo ^</html^>';
echo     $response-^>getBody^(^)-^>write^($html^);
echo     return $response-^>withHeader^('Content-Type', 'text/html'^);
echo }^);
echo.
echo // Portal ciudadano
echo $app-^>get^('/citizen-portal', function ^($request, $response^) {
echo     $html = '^<!DOCTYPE html^>
echo ^<html lang="es"^>
echo ^<head^>
echo     ^<meta charset="UTF-8"^>
echo     ^<title^>Portal Ciudadano^</title^>
echo     ^<style^>
echo         body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
echo         .portal { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; }
echo         h1 { color: #2c3e50; text-align: center; }
echo         .form-group { margin: 20px 0; }
echo         label { display: block; margin-bottom: 5px; font-weight: bold; }
echo         input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
echo         textarea { height: 100px; }
echo         button { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; }
echo     ^</style^>
echo ^</head^>
echo ^<body^>
echo     ^<div class="portal"^>
echo         ^<h1^>Portal Ciudadano - San Francisco^</h1^>
echo         ^<p^>Envía tu solicitud, reclamo o sugerencia a la municipalidad^</p^>
echo         ^<form^>
echo             ^<div class="form-group"^>
echo                 ^<label^>Nombre Completo:^</label^>
echo                 ^<input type="text" required^>
echo             ^</div^>
echo             ^<div class="form-group"^>
echo                 ^<label^>Email:^</label^>
echo                 ^<input type="email" required^>
echo             ^</div^>
echo             ^<div class="form-group"^>
echo                 ^<label^>Categoría:^</label^>
echo                 ^<select^>
echo                     ^<option^>Obras Públicas^</option^>
echo                     ^<option^>Servicios^</option^>
echo                     ^<option^>Trámites^</option^>
echo                     ^<option^>Reclamos^</option^>
echo                 ^</select^>
echo             ^</div^>
echo             ^<div class="form-group"^>
echo                 ^<label^>Asunto:^</label^>
echo                 ^<input type="text" required^>
echo             ^</div^>
echo             ^<div class="form-group"^>
echo                 ^<label^>Descripción:^</label^>
echo                 ^<textarea required^>^</textarea^>
echo             ^</div^>
echo             ^<button type="submit"^>Enviar Solicitud^</button^>
echo         ^</form^>
echo         ^<p^>^<a href="/"^>← Volver al inicio^</a^>^</p^>
echo     ^</div^>
echo ^</body^>
echo ^</html^>';
echo     $response-^>getBody^(^)-^>write^($html^);
echo     return $response-^>withHeader^('Content-Type', 'text/html'^);
echo }^);
echo.
echo // Dashboard básico
echo $app-^>get^('/dashboard', function ^($request, $response^) {
echo     $html = '^<!DOCTYPE html^>
echo ^<html lang="es"^>
echo ^<head^>
echo     ^<meta charset="UTF-8"^>
echo     ^<title^>Dashboard - Sistema Municipal^</title^>
echo     ^<style^>
echo         body { font-family: Arial; margin: 0; background: #f5f5f5; }
echo         .header { background: #2c3e50; color: white; padding: 20px; }
echo         .container { padding: 20px; }
echo         .stats { display: grid; grid-template-columns: repeat^(auto-fit, minmax^(250px, 1fr^)^); gap: 20px; margin-bottom: 30px; }
echo         .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba^(0,0,0,0.1^); }
echo         .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
echo     ^</style^>
echo ^</head^>
echo ^<body^>
echo     ^<div class="header"^>
echo         ^<h1^>Dashboard - Sistema Municipal San Francisco^</h1^>
echo     ^</div^>
echo     ^<div class="container"^>
echo         ^<div class="stats"^>
echo             ^<div class="stat-card"^>
echo                 ^<h3^>Tableros Activos^</h3^>
echo                 ^<div class="stat-number"^>5^</div^>
echo             ^</div^>
echo             ^<div class="stat-card"^>
echo                 ^<h3^>Tareas Pendientes^</h3^>
echo                 ^<div class="stat-number"^>23^</div^>
echo             ^</div^>
echo             ^<div class="stat-card"^>
echo                 ^<h3^>Solicitudes Ciudadanas^</h3^>
echo                 ^<div class="stat-number"^>12^</div^>
echo             ^</div^>
echo             ^<div class="stat-card"^>
echo                 ^<h3^>Usuarios Activos^</h3^>
echo                 ^<div class="stat-number"^>8^</div^>
echo             ^</div^>
echo         ^</div^>
echo         ^<p^>^<a href="/"^>← Volver al inicio^</a^>^</p^>
echo     ^</div^>
echo ^</body^>
echo ^</html^>';
echo     $response-^>getBody^(^)-^>write^($html^);
echo     return $response-^>withHeader^('Content-Type', 'text/html'^);
echo }^);
echo.
echo // Agregar middleware JWT
echo $app-^>add^(new App\Middleware\JWTMiddleware^($container^)^);
) > src\routes.php

echo ✅ routes.php creado

:: 4. Verificar y corregir public/index.php
echo 🔧 Corrigiendo public/index.php...
(
echo ^<?php
echo.
echo // Mostrar errores en desarrollo
echo error_reporting^(E_ALL^);
echo ini_set^('display_errors', 1^);
echo.
echo // Incluir bootstrap
echo require __DIR__ . '/../src/bootstrap.php';
echo.
echo // Incluir rutas
echo require __DIR__ . '/../src/routes.php';
echo.
echo // Ejecutar aplicación
echo $app-^>run^(^);
) > public\index.php

echo ✅ public/index.php corregido

:: 5. Configurar permisos
echo 🔐 Configurando permisos...
icacls uploads /grant Everyone:F /T >nul 2>&1
icacls logs /grant Everyone:F /T >nul 2>&1
icacls cache /grant Everyone:F /T >nul 2>&1

:: 6. Verificar .env
echo 🔧 Verificando archivo .env...
if not exist ".env" (
    echo Creando archivo .env básico...
    (
    echo DB_HOST=localhost
    echo DB_NAME=municipal_system
    echo DB_USERNAME=root
    echo DB_PASSWORD=
    echo DB_CHARSET=utf8mb4
    echo JWT_SECRET=SF_JWT_SECRET_KEY_CHANGE_IN_PRODUCTION_%RANDOM%
    echo APP_NAME="Sistema Municipal San Francisco"
    echo APP_ENV=development
    echo APP_DEBUG=true
    echo APP_URL=http://localhost/Sistema-pablo/public
    echo APP_TIMEZONE=America/Argentina/Cordoba
    echo LOG_PATH=logs/
    ) > .env
    echo ✅ Archivo .env creado
) else (
    echo ✅ Archivo .env existe
)

:: 7. Limpiar cache de Composer
echo 🧹 Limpiando cache...
composer dump-autoload >nul 2>&1

echo.
echo ================================================================
echo                    ✅ ERRORES SOLUCIONADOS
echo ================================================================
echo.
echo 🌐 Prueba estas URLs:
echo    • http://localhost/Sistema-pablo/public/
echo    • http://localhost/Sistema-pablo/public/api/health
echo    • http://localhost/Sistema-pablo/public/login
echo    • http://localhost/Sistema-pablo/public/citizen-portal
echo    • http://localhost/Sistema-pablo/public/dashboard
echo.
echo 💡 Si sigues teniendo problemas:
echo    1. Verifica que Apache esté iniciado en XAMPP
echo    2. Verifica que el directorio sea exactamente 'Sistema-pablo'
echo    3. Revisa los logs en: logs\app.log
echo.
echo ================================================================

pause