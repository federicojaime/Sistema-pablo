<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use App\Models\BoardMember;
use App\Models\ActivityLog;
use Respect\Validation\Validator as v;

class BoardController
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $queryParams = $request->getQueryParams();
            
            $boards = Board::with(['owner', 'members'])
                ->where(function($query) use ($user) {
                    $query->where('owner_id', $user->id)
                          ->orWhere('is_public', true)
                          ->orWhereHas('members', function($q) use ($user) {
                              $q->where('user_id', $user->id);
                          });
                })
                ->when(isset($queryParams['search']), function($query) use ($queryParams) {
                    $query->where('title', 'LIKE', '%' . $queryParams['search'] . '%');
                })
                ->orderBy('updated_at', 'desc')
                ->get();

            return $this->jsonResponse($response, [
                'error' => false,
                'data' => $boards
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error obteniendo tableros: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al obtener tableros'
            ], 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $boardId = $args['id'];

            $board = Board::with([
                'owner', 'members', 
                'columns.tasks.assignedUser', 
                'columns.tasks.creator',
                'columns.tasks.comments.user'
            ])->find($boardId);

            if (!$board) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tablero no encontrado'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canAccessBoard($user, $board)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para acceder a este tablero'
                ], 403);
            }

            return $this->jsonResponse($response, [
                'error' => false,
                'data' => $board
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error obteniendo tablero: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al obtener tablero'
            ], 500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();

            // Validar datos
            $validation = $this->validateBoardData($data);
            if (!$validation['valid']) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Datos inválidos',
                    'errors' => $validation['errors']
                ], 400);
            }

            // Crear tablero
            $board = Board::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#3498db',
                'owner_id' => $user->id,
                'is_public' => $data['is_public'] ?? false
            ]);

            // Crear columnas por defecto
            $defaultColumns = [
                ['title' => 'Por Hacer', 'position' => 0, 'color' => '#e74c3c'],
                ['title' => 'En Proceso', 'position' => 1, 'color' => '#f39c12'],
                ['title' => 'Revisión', 'position' => 2, 'color' => '#9b59b6'],
                ['title' => 'Completado', 'position' => 3, 'color' => '#27ae60']
            ];

            foreach ($defaultColumns as $columnData) {
                Column::create([
                    'board_id' => $board->id,
                    'title' => $columnData['title'],
                    'position' => $columnData['position'],
                    'color' => $columnData['color']
                ]);
            }

            // Agregar owner como miembro admin
            BoardMember::create([
                'board_id' => $board->id,
                'user_id' => $user->id,
                'role' => 'owner'
            ]);

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'create_board',
                'entity_type' => 'board',
                'entity_id' => $board->id,
                'details' => ['title' => $board->title]
            ]);

            $board->load(['owner', 'columns']);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tablero creado exitosamente',
                'data' => $board
            ], 201);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error creando tablero: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al crear tablero'
            ], 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $boardId = $args['id'];
            $data = $request->getParsedBody();

            $board = Board::find($boardId);
            if (!$board) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tablero no encontrado'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canEditBoard($user, $board)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para editar este tablero'
                ], 403);
            }

            // Validar datos
            $validation = $this->validateBoardData($data, false);
            if (!$validation['valid']) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Datos inválidos',
                    'errors' => $validation['errors']
                ], 400);
            }

            // Actualizar tablero
            $board->update([
                'title' => $data['title'] ?? $board->title,
                'description' => $data['description'] ?? $board->description,
                'color' => $data['color'] ?? $board->color,
                'is_public' => $data['is_public'] ?? $board->is_public
            ]);

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'update_board',
                'entity_type' => 'board',
                'entity_id' => $board->id,
                'details' => ['title' => $board->title]
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tablero actualizado exitosamente',
                'data' => $board
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error actualizando tablero: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al actualizar tablero'
            ], 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $boardId = $args['id'];

            $board = Board::find($boardId);
            if (!$board) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tablero no encontrado'
                ], 404);
            }

            // Solo el owner puede eliminar
            if ($board->owner_id !== $user->id && !$user->isAdmin()) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para eliminar este tablero'
                ], 403);
            }

            // Log de actividad antes de eliminar
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'delete_board',
                'entity_type' => 'board',
                'entity_id' => $board->id,
                'details' => ['title' => $board->title]
            ]);

            $board->delete();

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tablero eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error eliminando tablero: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al eliminar tablero'
            ], 500);
        }
    }

    public function addMember(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $boardId = $args['id'];
            $data = $request->getParsedBody();

            $board = Board::find($boardId);
            if (!$board) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tablero no encontrado'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canEditBoard($user, $board)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para agregar miembros'
                ], 403);
            }

            // Verificar si el miembro ya existe
            $existingMember = BoardMember::where('board_id', $boardId)
                                        ->where('user_id', $data['user_id'])
                                        ->first();

            if ($existingMember) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'El usuario ya es miembro del tablero'
                ], 409);
            }

            // Agregar miembro
            BoardMember::create([
                'board_id' => $boardId,
                'user_id' => $data['user_id'],
                'role' => $data['role'] ?? 'member'
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Miembro agregado exitosamente'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al agregar miembro'
            ], 500);
        }
    }

    private function validateBoardData($data, $required = true): array
    {
        $errors = [];

        if ($required && !v::stringType()->notEmpty()->validate($data['title'] ?? '')) {
            $errors['title'] = 'Título requerido';
        }

        if (isset($data['color']) && !v::regex('/^#[0-9A-Fa-f]{6}$/')->validate($data['color'])) {
            $errors['color'] = 'Color debe ser un código hexadecimal válido';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function canAccessBoard($user, $board): bool
    {
        return $board->owner_id === $user->id || 
               $board->is_public || 
               $board->members->contains('id', $user->id) ||
               $user->isAdmin();
    }

    private function canEditBoard($user, $board): bool
    {
        if ($user->isAdmin() || $board->owner_id === $user->id) {
            return true;
        }

        $member = $board->members->where('id', $user->id)->first();
        return $member && in_array($member->pivot->role, ['admin', 'owner']);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}