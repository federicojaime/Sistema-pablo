<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Task;
use App\Models\Column;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Services\NotificationService;
use Respect\Validation\Validator as v;

class TaskController
{
    private $container;
    private $notificationService;

    public function __construct($container)
    {
        $this->container = $container;
        $this->notificationService = new NotificationService($container);
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $queryParams = $request->getQueryParams();
            
            $tasks = Task::with(['column.board', 'assignedUser', 'creator', 'comments'])
                ->whereHas('column.board', function($query) use ($user) {
                    $query->where('owner_id', $user->id)
                          ->orWhere('is_public', true)
                          ->orWhereHas('members', function($q) use ($user) {
                              $q->where('user_id', $user->id);
                          });
                })
                ->when(isset($queryParams['assigned_to']), function($query) use ($queryParams) {
                    $query->where('assigned_to', $queryParams['assigned_to']);
                })
                ->when(isset($queryParams['status']), function($query) use ($queryParams) {
                    $query->where('status', $queryParams['status']);
                })
                ->when(isset($queryParams['priority']), function($query) use ($queryParams) {
                    $query->where('priority', $queryParams['priority']);
                })
                ->when(isset($queryParams['search']), function($query) use ($queryParams) {
                    $query->where(function($q) use ($queryParams) {
                        $q->where('title', 'LIKE', '%' . $queryParams['search'] . '%')
                          ->orWhere('description', 'LIKE', '%' . $queryParams['search'] . '%');
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->jsonResponse($response, [
                'error' => false,
                'data' => $tasks
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error obteniendo tareas: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al obtener tareas'
            ], 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $taskId = $args['id'];

            $task = Task::with([
                'column.board.owner', 
                'assignedUser', 
                'creator',
                'comments.user',
                'files'
            ])->find($taskId);

            if (!$task) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canAccessTask($user, $task)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para acceder a esta tarea'
                ], 403);
            }

            return $this->jsonResponse($response, [
                'error' => false,
                'data' => $task
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error obteniendo tarea: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al obtener tarea'
            ], 500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();

            // Validar datos
            $validation = $this->validateTaskData($data);
            if (!$validation['valid']) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Datos inválidos',
                    'errors' => $validation['errors']
                ], 400);
            }

            // Verificar que la columna existe y el usuario tiene permisos
            $column = Column::with('board')->find($data['column_id']);
            if (!$column || !$this->canAccessBoard($user, $column->board)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Columna no encontrada o sin permisos'
                ], 404);
            }

            // Obtener la posición para la nueva tarea
            $maxPosition = Task::where('column_id', $data['column_id'])->max('position') ?? -1;

            // Crear tarea
            $task = Task::create([
                'column_id' => $data['column_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'pending',
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $user->id,
                'due_date' => $data['due_date'] ?? null,
                'position' => $maxPosition + 1,
                'tags' => $data['tags'] ?? []
            ]);

            // Enviar notificación si se asignó a alguien
            if ($task->assigned_to && $task->assigned_to !== $user->id) {
                $this->notificationService->sendTaskAssignedNotification($task);
            }

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'create_task',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'details' => [
                    'title' => $task->title,
                    'board' => $column->board->title
                ]
            ]);

            $task->load(['assignedUser', 'creator', 'column']);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tarea creada exitosamente',
                'data' => $task
            ], 201);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error creando tarea: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al crear tarea'
            ], 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $taskId = $args['id'];
            $data = $request->getParsedBody();

            $task = Task::with('column.board')->find($taskId);
            if (!$task) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canEditTask($user, $task)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para editar esta tarea'
                ], 403);
            }

            $oldAssignedTo = $task->assigned_to;
            $oldStatus = $task->status;

            // Actualizar tarea
            $task->update([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'priority' => $data['priority'] ?? $task->priority,
                'status' => $data['status'] ?? $task->status,
                'assigned_to' => $data['assigned_to'] ?? $task->assigned_to,
                'due_date' => $data['due_date'] ?? $task->due_date,
                'tags' => $data['tags'] ?? $task->tags
            ]);

            // Enviar notificaciones según los cambios
            if ($task->assigned_to !== $oldAssignedTo && $task->assigned_to && $task->assigned_to !== $user->id) {
                $this->notificationService->sendTaskAssignedNotification($task);
            }

            if ($task->status !== $oldStatus && $task->status === 'completed') {
                $this->notificationService->sendTaskCompletedNotification($task);
            }

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'update_task',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'details' => [
                    'title' => $task->title,
                    'changes' => array_intersect_key($data, array_flip(['status', 'priority', 'assigned_to']))
                ]
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tarea actualizada exitosamente',
                'data' => $task
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error actualizando tarea: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al actualizar tarea'
            ], 500);
        }
    }

    public function move(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $taskId = $args['id'];
            $data = $request->getParsedBody();

            $task = Task::with('column.board')->find($taskId);
            if (!$task) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canEditTask($user, $task)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para mover esta tarea'
                ], 403);
            }

            $oldColumnId = $task->column_id;
            $newColumnId = $data['column_id'];
            $newPosition = $data['position'] ?? 0;

            // Si cambió de columna, actualizar posiciones
            if ($oldColumnId !== $newColumnId) {
                // Verificar que la nueva columna existe y pertenece al mismo tablero
                $newColumn = Column::with('board')->find($newColumnId);
                if (!$newColumn || $newColumn->board_id !== $task->column->board_id) {
                    return $this->jsonResponse($response, [
                        'error' => true,
                        'message' => 'Columna de destino inválida'
                    ], 400);
                }

                // Actualizar posiciones en la columna origen
                Task::where('column_id', $oldColumnId)
                    ->where('position', '>', $task->position)
                    ->decrement('position');

                // Actualizar posiciones en la columna destino
                Task::where('column_id', $newColumnId)
                    ->where('position', '>=', $newPosition)
                    ->increment('position');
            } else {
                // Mover dentro de la misma columna
                if ($newPosition > $task->position) {
                    Task::where('column_id', $oldColumnId)
                        ->whereBetween('position', [$task->position + 1, $newPosition])
                        ->decrement('position');
                } else {
                    Task::where('column_id', $oldColumnId)
                        ->whereBetween('position', [$newPosition, $task->position - 1])
                        ->increment('position');
                }
            }

            // Actualizar la tarea
            $task->update([
                'column_id' => $newColumnId,
                'position' => $newPosition
            ]);

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'move_task',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'details' => [
                    'title' => $task->title,
                    'from_column' => $oldColumnId,
                    'to_column' => $newColumnId
                ]
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tarea movida exitosamente'
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error moviendo tarea: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al mover tarea'
            ], 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $taskId = $args['id'];

            $task = Task::with('column.board')->find($taskId);
            if (!$task) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canDeleteTask($user, $task)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para eliminar esta tarea'
                ], 403);
            }

            // Log de actividad antes de eliminar
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'delete_task',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'details' => [
                    'title' => $task->title,
                    'board' => $task->column->board->title
                ]
            ]);

            // Actualizar posiciones de las tareas siguientes
            Task::where('column_id', $task->column_id)
                ->where('position', '>', $task->position)
                ->decrement('position');

            $task->delete();

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Tarea eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error eliminando tarea: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al eliminar tarea'
            ], 500);
        }
    }

    public function addComment(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $taskId = $args['id'];
            $data = $request->getParsedBody();

            $task = Task::with('column.board')->find($taskId);
            if (!$task) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            // Verificar permisos
            if (!$this->canAccessTask($user, $task)) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'Sin permisos para comentar en esta tarea'
                ], 403);
            }

            if (empty($data['content'])) {
                return $this->jsonResponse($response, [
                    'error' => true,
                    'message' => 'El contenido del comentario no puede estar vacío'
                ], 400);
            }

            // Crear comentario
            $comment = Comment::create([
                'task_id' => $taskId,
                'user_id' => $user->id,
                'content' => $data['content']
            ]);

            $comment->load('user');

            // Enviar notificación
            $this->notificationService->sendCommentAddedNotification($task, $comment);

            // Log de actividad
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'add_comment',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'details' => [
                    'task_title' => $task->title,
                    'comment_id' => $comment->id
                ]
            ]);

            return $this->jsonResponse($response, [
                'error' => false,
                'message' => 'Comentario agregado exitosamente',
                'data' => $comment
            ], 201);

        } catch (\Exception $e) {
            $this->container->get('logger')->error('Error agregando comentario: ' . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => true,
                'message' => 'Error al agregar comentario'
            ], 500);
        }
    }

    private function validateTaskData($data): array
    {
        $errors = [];

        if (!v::stringType()->notEmpty()->validate($data['title'] ?? '')) {
            $errors['title'] = 'Título requerido';
        }

        if (!v::intVal()->positive()->validate($data['column_id'] ?? '')) {
            $errors['column_id'] = 'ID de columna inválido';
        }

        if (isset($data['priority']) && !in_array($data['priority'], ['low', 'medium', 'high', 'urgent'])) {
            $errors['priority'] = 'Prioridad inválida';
        }

        if (isset($data['status']) && !in_array($data['status'], ['pending', 'in_progress', 'completed', 'cancelled'])) {
            $errors['status'] = 'Estado inválido';
        }

        if (isset($data['due_date']) && !v::date('Y-m-d H:i:s')->validate($data['due_date'])) {
            $errors['due_date'] = 'Fecha de vencimiento inválida';
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

    private function canAccessTask($user, $task): bool
    {
        return $this->canAccessBoard($user, $task->column->board);
    }

    private function canEditTask($user, $task): bool
    {
        // El creador, asignado, admin del tablero o admin del sistema pueden editar
        return $task->created_by === $user->id ||
               $task->assigned_to === $user->id ||
               $task->column->board->owner_id === $user->id ||
               $user->isAdmin();
    }

    private function canDeleteTask($user, $task): bool
    {
        // Solo el creador, admin del tablero o admin del sistema pueden eliminar
        return $task->created_by === $user->id ||
               $task->column->board->owner_id === $user->id ||
               $user->isAdmin();
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}