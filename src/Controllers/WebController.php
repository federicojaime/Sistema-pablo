<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WebController
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function home(Request $request, Response $response): Response
    {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Municipal San Francisco</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero {
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 40px;
        }
        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .hero p {
            font-size: 1.3em;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        .buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .btn-primary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>Sistema Municipal</h1>
        <h2 style="margin-bottom: 30px;">San Francisco</h2>
        <p>Plataforma integral para la gestión municipal y atención ciudadana</p>
        <div class="buttons">
            <a href="/login" class="btn btn-primary">Ingresar al Sistema</a>
            <a href="/citizen-portal" class="btn btn-success">Portal Ciudadano</a>
        </div>
    </div>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function login(Request $request, Response $response): Response
    {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Municipal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #2c3e50;
            font-size: 2em;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.2);
            background: white;
        }
        .form-control i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        .input-group {
            position: relative;
        }
        .input-group .form-control {
            padding-left: 50px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .alert-error {
            background: #ffe6e6;
            color: #c0392b;
            border: 1px solid #f8d7da;
        }
        .alert-success {
            background: #e6f7e6;
            color: #229954;
            border: 1px solid #d4edda;
        }
        .footer-links {
            text-align: center;
            margin-top: 25px;
        }
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9em;
            margin: 0 10px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-city"></i> Login</h1>
            <p>Sistema Municipal San Francisco</p>
        </div>
        
        <div id="alertContainer"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" id="email" name="email" 
                           placeholder="Email o Usuario" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Contraseña" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" id="loginBtn">
                <span id="loginText">Iniciar Sesión</span>
                <span class="loading" id="loginLoading"></span>
            </button>
        </form>
        
        <div class="footer-links">
            <a href="/">← Volver al inicio</a>
            <a href="/citizen-portal">Portal Ciudadano</a>
        </div>
    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const loginBtn = document.getElementById("loginBtn");
            const loginText = document.getElementById("loginText");
            const loginLoading = document.getElementById("loginLoading");
            const alertContainer = document.getElementById("alertContainer");
            
            // Mostrar loading
            loginBtn.disabled = true;
            loginText.style.display = "none";
            loginLoading.style.display = "inline-block";
            
            const formData = new FormData(e.target);
            const loginData = {
                email: formData.get("email"),
                password: formData.get("password")
            };
            
            try {
                const response = await fetch("/api/auth/login", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(loginData)
                });
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.message);
                }
                
                // Guardar token y redirigir
                localStorage.setItem("authToken", data.data.token);
                localStorage.setItem("currentUser", JSON.stringify(data.data.user));
                
                showAlert("Inicio de sesión exitoso. Redirigiendo...", "success");
                
                setTimeout(() => {
                    window.location.href = "/dashboard";
                }, 1000);
                
            } catch (error) {
                showAlert(error.message, "error");
            } finally {
                // Ocultar loading
                loginBtn.disabled = false;
                loginText.style.display = "inline";
                loginLoading.style.display = "none";
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById("alertContainer");
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = "";
            }, 5000);
        }
        
        // Auto-focus en el primer campo
        document.getElementById("email").focus();
    </script>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function citizenPortal(Request $request, Response $response): Response
    {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Ciudadano - San Francisco</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2c3e50;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            padding: 20px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo h1 {
            color: #2c3e50;
            font-size: 1.8em;
        }
        .main-content {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
            text-align: center;
        }
        .welcome-card h2 {
            font-size: 2.2em;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .welcome-card p {
            font-size: 1.1em;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .request-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        .form-title {
            font-size: 1.5em;
            margin-bottom: 25px;
            color: #2c3e50;
            text-align: center;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.2);
            background: white;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 1em;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .categories-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .category-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .category-item:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        .category-item i {
            font-size: 2em;
            margin-bottom: 10px;
            color: #3498db;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-city" style="font-size: 2em; color: #3498db;"></i>
                <div>
                    <h1>Portal Ciudadano</h1>
                    <div style="font-size: 0.8em; color: #7f8c8d;">Municipalidad de San Francisco</div>
                </div>
            </div>
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Volver al Inicio
            </a>
        </div>
    </header>

    <div class="main-content">
        <div class="welcome-card">
            <h2><i class="fas fa-handshake"></i> Bienvenido/a</h2>
            <p>Utiliza este portal para enviar solicitudes, reclamos o sugerencias a la municipalidad. 
               Tu participación es importante para mejorar nuestra ciudad.</p>
        </div>

        <div class="categories-info">
            <div class="category-item">
                <i class="fas fa-road"></i>
                <div><strong>Obras Públicas</strong></div>
                <div style="font-size: 0.9em; color: #7f8c8d;">Calles, veredas, alumbrado</div>
            </div>
            <div class="category-item">
                <i class="fas fa-recycle"></i>
                <div><strong>Servicios</strong></div>
                <div style="font-size: 0.9em; color: #7f8c8d;">Limpieza, recolección</div>
            </div>
            <div class="category-item">
                <i class="fas fa-file-alt"></i>
                <div><strong>Trámites</strong></div>
                <div style="font-size: 0.9em; color: #7f8c8d;">Documentación, permisos</div>
            </div>
            <div class="category-item">
                <i class="fas fa-exclamation-triangle"></i>
                <div><strong>Reclamos</strong></div>
                <div style="font-size: 0.9em; color: #7f8c8d;">Problemas a resolver</div>
            </div>
        </div>

        <div class="request-form">
            <h3 class="form-title">
                <i class="fas fa-paper-plane"></i>
                Nueva Solicitud
            </h3>
            
            <div id="alertContainer"></div>
            
            <form id="citizenRequestForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="citizen_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="citizen_email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" name="citizen_phone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">DNI</label>
                        <input type="text" class="form-control" name="citizen_dni">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Categoría *</label>
                        <select class="form-control" name="category" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="obras_publicas">Obras Públicas</option>
                            <option value="servicios">Servicios</option>
                            <option value="tramites">Trámites</option>
                            <option value="reclamos">Reclamos</option>
                            <option value="sugerencias">Sugerencias</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prioridad</label>
                        <select class="form-control" name="priority">
                            <option value="medium">Media</option>
                            <option value="low">Baja</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Asunto *</label>
                    <input type="text" class="form-control" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descripción *</label>
                    <textarea class="form-control" name="description" required 
                              placeholder="Describe detalladamente tu solicitud, reclamo o sugerencia..."></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        <span id="submitText">Enviar Solicitud</span>
                        <span class="loading" id="submitLoading" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById("citizenRequestForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById("submitBtn");
            const submitText = document.getElementById("submitText");
            const submitLoading = document.getElementById("submitLoading");
            
            // Mostrar loading
            submitBtn.disabled = true;
            submitText.style.display = "none";
            submitLoading.style.display = "inline-block";
            
            const formData = new FormData(e.target);
            const requestData = {
                citizen_name: formData.get("citizen_name"),
                citizen_email: formData.get("citizen_email"),
                citizen_phone: formData.get("citizen_phone"),
                citizen_dni: formData.get("citizen_dni"),
                category: formData.get("category"),
                priority: formData.get("priority"),
                subject: formData.get("subject"),
                description: formData.get("description")
            };
            
            try {
                const response = await fetch("/api/citizen-requests", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(requestData)
                });
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.message);
                }
                
                showAlert(data.message + " Tu solicitud ha sido registrada con el ID: " + data.data.request_id, "success");
                e.target.reset();
                
            } catch (error) {
                showAlert("Error: " + error.message, "error");
            } finally {
                // Ocultar loading
                submitBtn.disabled = false;
                submitText.style.display = "inline";
                submitLoading.style.display = "none";
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById("alertContainer");
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = "";
            }, 8000);
        }
    </script>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function dashboard(Request $request, Response $response): Response
    {
        // Aquí cargarías el dashboard HTML que creamos anteriormente
        $dashboardHtml = file_get_contents(__DIR__ . '/../Views/dashboard.html');
        $response->getBody()->write($dashboardHtml);
        return $response->withHeader('Content-Type', 'text/html');
    }
}