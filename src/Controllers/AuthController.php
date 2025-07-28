<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Models\User;
use App\Models\ActivityLog;
use Respect\Validation\Validator as v;

class AuthController
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validar datos
            $validation = $this->validateLoginData($data);
            if (!$validation['valid']) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Datos inválidos',
                    'errors' => $validation['errors']
                ], 400);
            }

            // Buscar usuario
            $user = User::where('email', $data['email'])
                       ->orWhere('username', $data['email'])
                       ->first();

            if (!$user || !$user->verifyPassword($data['password'])) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            if (!$user->is_active) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Usuario desactivado'
                ], 401);
            }

            // Generar JWT
            $payload = [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'iat' => time(),
                'exp' => time() + (int)$_ENV['JWT_EXPIRATION']
            ];

            $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'ip_address' => $this->getClientIP($request),
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Login exitoso',
                'data' => [
                    'token' => $token,
                    'user' => $user->toArray(),
                    'expires_in' => (int)$_ENV['JWT_EXPIRATION']
                ]
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error en login: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validar datos
            $validation = $this->validateRegisterData($data);
            if (!$validation['valid']) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Datos inválidos',
                    'errors' => $validation['errors']
                ], 400);
            }

            // Verificar si el usuario ya existe
            $existingUser = User::where('email', $data['email'])
                               ->orWhere('username', $data['username'])
                               ->first();

            if ($existingUser) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'El usuario ya existe'
                ], 409);
            }

            // Crear usuario
            $user = new User();
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->setPassword($data['password']);
            $user->full_name = $data['full_name'];
            $user->department = $data['department'] ?? null;
            $user->role = $data['role'] ?? 'employee';
            $user->save();

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'register',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'ip_address' => $this->getClientIP($request),
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Usuario registrado exitosamente',
                'data' => $user->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error en registro: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'ip_address' => $this->getClientIP($request),
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Logout exitoso'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error en logout'
            ], 500);
        }
    }

    public function me(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        return $this->jsonResponse($response, [
            'error' => false,
            'data' => [
                'user' => $user->toArray(),
                'unread_notifications' => $user->getUnreadNotificationsCount(),
                'unread_messages' => $user->getUnreadMessagesCount()
            ]
        ]);
    }

    private function validateLoginData($data): array
    {
        $errors = [];

        if (!v::email()->validate($data['email'] ?? '') && !v::alnum()->validate($data['email'] ?? '')) {
            $errors['email'] = 'Email o usuario inválido';
        }

        if (!v::stringType()->notEmpty()->validate($data['password'] ?? '')) {
            $errors['password'] = 'Contraseña requerida';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function validateRegisterData($data): array
    {
        $errors = [];

        if (!v::stringType()->notEmpty()->length(3, 50)->validate($data['username'] ?? '')) {
            $errors['username'] = 'Usuario debe tener entre 3 y 50 caracteres';
        }

        if (!v::email()->validate($data['email'] ?? '')) {
            $errors['email'] = 'Email inválido';
        }

        if (!v::stringType()->length(6, null)->validate($data['password'] ?? '')) {
            $errors['password'] = 'Contraseña debe tener al menos 6 caracteres';
        }

        if (!v::stringType()->notEmpty()->validate($data['full_name'] ?? '')) {
            $errors['full_name'] = 'Nombre completo requerido';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function getClientIP($request): string
    {
        $serverParams = $request->getServerParams();
        
        if (!empty($serverParams['HTTP_CLIENT_IP'])) {
            return $serverParams['HTTP_CLIENT_IP'];
        } elseif (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            return $serverParams['HTTP_X_FORWARDED_FOR'];
        } else {
            return $serverParams['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}