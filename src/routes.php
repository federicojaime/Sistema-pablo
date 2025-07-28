<?php

// Ruta principal
$app->get('/', function ($request, $response) {
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
  <base href="/sistema-pablo/public/">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Municipal San Francisco</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #2c3e50;
        }
        .container { 
            max-width: 900px; 
            margin: 50px auto; 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px; 
            border-radius: 25px; 
            box-shadow: 0 25px 80px rgba(0,0,0,0.2);
        }
        h1 { 
            text-align: center; 
            margin-bottom: 30px;
            font-size: 2.8em;
            font-weight: 300;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .status { 
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #c3e6cb; 
            color: #155724; 
            padding: 25px; 
            border-radius: 15px; 
            margin: 30px 0;
            text-align: center;
            font-size: 1.3em;
            font-weight: 500;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.1);
        }
        .links { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
            margin: 40px 0;
        }
        .link { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
            padding: 35px 25px; 
            text-align: center; 
            text-decoration: none; 
            border-radius: 20px; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1.1em;
            font-weight: 500;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .link:hover { 
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.4);
        }
        .link i {
            font-size: 2.5em;
            margin-bottom: 15px;
            display: block;
            opacity: 0.9;
        }
        .info {
            background: rgba(248, 249, 250, 0.8);
            padding: 25px;
            border-radius: 15px;
            margin-top: 40px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .info h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1.2em;
        }
        .info p {
            margin: 8px 0;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .container { padding: 30px; margin: 20px auto; }
            h1 { font-size: 2.2em; }
            .links { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-city"></i> Sistema Municipal San Francisco</h1>
        
        <div class="status">
            <i class="fas fa-check-circle"></i> Sistema instalado y funcionando correctamente
        </div>
        
        <div class="links">
            <a href="/login" class="link">
                <i class="fas fa-sign-in-alt"></i>
                Acceso Administrativo
            </a>
            <a href="/citizen-portal" class="link">
                <i class="fas fa-users"></i>
                Portal Ciudadano
            </a>
            <a href="/dashboard" class="link">
                <i class="fas fa-chart-pie"></i>
                Dashboard Municipal
            </a>
            <a href="/api/health" class="link">
                <i class="fas fa-heartbeat"></i>
                Estado del Sistema
            </a>
        </div>
        
        <div class="info">
            <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
            <p><strong>Versión:</strong> 1.0.0</p>
            <p><strong>Instalado:</strong> ' . date('d/m/Y H:i') . '</p>
            <p><strong>Estado:</strong> <span style="color: #28a745;">Operativo</span></p>
            <p><strong>Framework:</strong> Slim PHP 4 + MySQL</p>
            <p><strong>Servidor:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP') . '</p>
        </div>
    </div>
</body>
</html>';

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});

// API de salud del sistema
$app->get('/api/health', function ($request, $response) {
    $health = [
        'status' => 'healthy',
        'message' => 'Sistema Municipal funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'system' => [
            'os' => PHP_OS,
            'timezone' => date_default_timezone_get(),
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring')
            ]
        ]
    ];

    $response->getBody()->write(json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

// Página de login
$app->get('/login', function ($request, $response) {
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Municipal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            padding: 50px; 
            border-radius: 25px; 
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        h2 { 
            text-align: center; 
            margin-bottom: 30px; 
            color: #2c3e50;
            font-size: 2em;
            font-weight: 300;
        }
        .demo-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1565c0;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
        }
        input { 
            width: 100%; 
            padding: 15px 20px; 
            border: 2px solid #e9ecef; 
            border-radius: 12px; 
            box-sizing: border-box;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        button { 
            width: 100%; 
            padding: 18px; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        .back { 
            text-align: center; 
            margin-top: 30px; 
        }
        .back a { 
            color: #667eea; 
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .back a:hover {
            color: #5a6fd8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="fas fa-lock"></i> Acceso al Sistema</h2>
        
        <div class="demo-info">
            <strong><i class="fas fa-info-circle"></i> Versión Demo</strong><br>
            Email: admin@sanfrancisco.gov.ar<br>
            Contraseña: password
        </div>
        
        <form onsubmit="handleLogin(event)">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" value="admin@sanfrancisco.gov.ar" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Contraseña:</label>
                <input type="password" id="password" name="password" value="password" required>
            </div>
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="back">
            <a href="/"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
        </div>
    </div>
    
    <script>
        function handleLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            
            if (email === "admin@sanfrancisco.gov.ar" && password === "password") {
                alert("¡Login exitoso!\\n\\nRedirigiendo al dashboard...");
                window.location.href = "/dashboard";
            } else {
                alert("Credenciales incorrectas.\\n\\nUsa las credenciales de demo mostradas arriba.");
            }
        }
    </script>
</body>
</html>';

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});

// Portal ciudadano
$app->get('/citizen-portal', function ($request, $response) {
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Ciudadano - San Francisco</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            padding: 50px; 
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.2);
        }
        h1 { 
            color: #2c3e50; 
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.5em;
            font-weight: 300;
        }
        .subtitle {
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.2em;
            color: #6c757d;
        }
        .categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .category {
            background: rgba(248, 249, 250, 0.8);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .category:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }
        .category i {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 15px;
        }
        .category h3 {
            margin: 10px 0;
            color: #2c3e50;
        }
        .form-group { 
            margin: 25px 0; 
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: #495057;
        }
        input, select, textarea { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #e9ecef; 
            border-radius: 12px; 
            box-sizing: border-box;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        textarea { 
            height: 130px; 
            resize: vertical;
        }
        button { 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white; 
            padding: 18px 35px; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            display: none;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-building"></i> Portal Ciudadano</h1>
        <div class="subtitle">Municipalidad de San Francisco - Tu voz cuenta</div>
        
        <div class="categories">
            <div class="category">
                <i class="fas fa-road"></i>
                <h3>Obras Públicas</h3>
                <p>Calles, veredas, alumbrado</p>
            </div>
            <div class="category">
                <i class="fas fa-recycle"></i>
                <h3>Servicios</h3>
                <p>Limpieza, recolección</p>
            </div>
            <div class="category">
                <i class="fas fa-file-alt"></i>
                <h3>Trámites</h3>
                <p>Documentación, permisos</p>
            </div>
            <div class="category">
                <i class="fas fa-lightbulb"></i>
                <h3>Sugerencias</h3>
                <p>Ideas y mejoras</p>
            </div>
        </div>
        
        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i> ¡Solicitud enviada exitosamente!<br>
            <strong>Número de seguimiento:</strong> <span id="requestNumber"></span>
        </div>
        
        <form id="citizenForm" onsubmit="handleSubmit(event)">
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Nombre Completo *:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email *:</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Teléfono:</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="category"><i class="fas fa-tags"></i> Categoría *:</label>
                    <select id="category" name="category" required>
                        <option value="">Seleccionar categoría</option>
                        <option value="obras_publicas">Obras Públicas</option>
                        <option value="servicios">Servicios</option>
                        <option value="tramites">Trámites</option>
                        <option value="reclamos">Reclamos</option>
                        <option value="sugerencias">Sugerencias</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="subject"><i class="fas fa-heading"></i> Asunto *:</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-comment"></i> Descripción detallada *:</label>
                <textarea id="description" name="description" placeholder="Describe tu solicitud de manera clara y detallada..." required></textarea>
            </div>
            
            <div style="text-align: center;">
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Enviar Solicitud
                </button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="/" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 1.1em;">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </div>
    
    <script>
        function handleSubmit(event) {
            event.preventDefault();
            
            // Generar número de solicitud
            const year = new Date().getFullYear();
            const number = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
            const requestId = `SF-${year}-${number}`;
            
            // Mostrar mensaje de éxito
            document.getElementById("requestNumber").textContent = requestId;
            document.getElementById("successMessage").style.display = "block";
            
            // Limpiar formulario
            document.getElementById("citizenForm").reset();
            
            // Scroll al mensaje
            document.getElementById("successMessage").scrollIntoView({ behavior: "smooth" });
        }
    </script>
</body>
</html>';

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});

// Dashboard
$app->get('/dashboard', function ($request, $response) {
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Municipal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background: #f8f9fa;
        }
        .header { 
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white; 
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 300;
        }
        .container { 
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 30px; 
            margin-bottom: 50px; 
        }
        .stat-card { 
            background: white; 
            padding: 35px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border-left: 6px solid #667eea;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-8px);
        }
        .stat-card h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .stat-number { 
            font-size: 3em; 
            font-weight: 300; 
            color: #667eea;
            margin-bottom: 15px;
            line-height: 1;
        }
        .stat-change {
            font-size: 0.9em;
            color: #28a745;
            font-weight: 500;
        }
        .recent-activity {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }
        .recent-activity h2 {
            margin: 0 0 30px 0;
            color: #2c3e50;
            font-size: 1.5em;
            font-weight: 400;
        }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.2em;
        }
        .activity-text {
            flex: 1;
        }
        .activity-text strong {
            color: #2c3e50;
        }
        .activity-time {
            color: #6c757d;
            font-size: 0.9em;
            font-weight: 500;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        .quick-action {
            background: white;
            border: 3px dashed #dee2e6;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #6c757d;
        }
        .quick-action:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.15);
        }
        .quick-action i {
            font-size: 3em;
            margin-bottom: 20px;
            display: block;
        }
        .quick-action h3 {
            margin: 0;
            font-size: 1.1em;
        }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .stats { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-chart-pie"></i> Dashboard Municipal - San Francisco</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Panel de control y gestión integral</p>
    </div>
    
    <div class="container">
        <div class="quick-actions">
            <a href="#" class="quick-action" onclick="alert(\'Funcionalidad en desarrollo\')"
>
                <i class="fas fa-plus"></i>
                <h3>Nuevo Tablero</h3>
            </a>
            <a href="#" class="quick-action"onclick="alert(\'Funcionalidad en desarrollo\')"
>
                <i class="fas fa-tasks"></i>
                <h3>Nueva Tarea</h3>
            </a>
            <a href="#" class="quick-action" onclick="alert(\'Funcionalidad en desarrollo\')"
>
                <i class="fas fa-inbox"></i>
                <h3>Ver Solicitudes</h3>
            </a>
            <a href="#" class="quick-action" onclick="alert(\'Funcionalidad en desarrollo\')">
                <i class="fas fa-paper-plane"></i>
                <h3>Enviar Mensaje</h3>
            </a>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Tableros Activos</h3>
                <div class="stat-number">12</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i> +3 este mes
                </div>
            </div>
            <div class="stat-card">
                <h3>Tareas Pendientes</h3>
                <div class="stat-number">47</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-down"></i> -8 esta semana
                </div>
            </div>
            <div class="stat-card">
                <h3>Solicitudes Ciudadanas</h3>
                <div class="stat-number">89</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i> +15 hoy
                </div>
            </div>
            <div class="stat-card">
                <h3>Usuarios Activos</h3>
                <div class="stat-number">24</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i> +6 este mes
                </div>
            </div>
        </div>
        
        <div class="recent-activity">
            <h2><i class="fas fa-clock"></i> Actividad Reciente</h2>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="activity-text">
                    <strong>Juan Silva</strong> creó una nueva tarea en "Gestión de Obras Públicas"
                    <br><small style="color: #6c757d;">Reparación de luminarias en Av. Principal</small>
                </div>
                <div class="activity-time">Hace 8 min</div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="activity-text">
                    <strong>María Rodríguez</strong> completó la tarea "Revisión de expedientes municipales"
                    <br><small style="color: #6c757d;">Total: 15 expedientes procesados</small>
                </div>
                <div class="activity-time">Hace 22 min</div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-comment"></i>
                </div>
                <div class="activity-text">
                    <strong>Carlos Pérez</strong> agregó un comentario en "Mantenimiento de plazas"
                    <br><small style="color: #6c757d;">Solicitud de materiales adicionales</small>
                </div>
                <div class="activity-time">Hace 45 min</div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="activity-text">
                    <strong>Sistema</strong> recibió una nueva solicitud ciudadana
                    <br><small style="color: #6c757d;">Categoría: Servicios - Recolección de residuos</small>
                </div>
                <div class="activity-time">Hace 1 h</div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-text">
                    <strong>Ana García</strong> se unió al tablero "Desarrollo Social"
                    <br><small style="color: #6c757d;">Rol: Colaborador</small>
                </div>
                <div class="activity-time">Hace 2 h</div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-flag"></i>
                </div>
                <div class="activity-text">
                    <strong>Roberto Martín</strong> marcó como urgente "Reparación de semáforo"
                    <br><small style="color: #6c757d;">Intersección Av. San Martín y Belgrano</small>
                </div>
                <div class="activity-time">Hace 3 h</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 50px;">
            <a href="/" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 1.2em;">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>';

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});
