<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Task;
use App\Models\Comment;
use PHPMailer\PHPMailer\PHPMailer;

class NotificationService
{
    private $container;
    private $mailer;

    public function __construct($container)
    {
        $this->container = $container;
        $this->mailer = $container->get('mailer');
    }

    public function sendTaskAssignedNotification(Task $task): void
    {
        if (!$task->assignedUser) {
            return;
        }

        $notification = Notification::create([
            'user_id' => $task->assigned_to,
            'type' => 'task_assigned',
            'title' => 'Nueva tarea asignada',
            'message' => "Se te ha asignado la tarea: {$task->title}",
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'board_title' => $task->column->board->title,
                'assigned_by' => $task->creator->full_name
            ]
        ]);

        $this->sendEmail($notification);
    }

    public function sendTaskCompletedNotification(Task $task): void
    {
        // Notificar al creador de la tarea si no es el mismo que la completó
        if ($task->creator && $task->creator->id !== $task->assigned_to) {
            $notification = Notification::create([
                'user_id' => $task->created_by,
                'type' => 'task_completed',
                'title' => 'Tarea completada',
                'message' => "La tarea '{$task->title}' ha sido completada",
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'board_title' => $task->column->board->title,
                    'completed_by' => $task->assignedUser ? $task->assignedUser->full_name : 'Usuario desconocido'
                ]
            ]);

            $this->sendEmail($notification);
        }
    }

    public function sendCommentAddedNotification(Task $task, Comment $comment): void
    {
        $usersToNotify = collect();

        // Notificar al asignado (si no es quien comentó)
        if ($task->assignedUser && $task->assigned_to !== $comment->user_id) {
            $usersToNotify->push($task->assignedUser);
        }

        // Notificar al creador (si no es quien comentó)
        if ($task->creator && $task->created_by !== $comment->user_id) {
            $usersToNotify->push($task->creator);
        }

        // Notificar a otros usuarios que han comentado en la tarea
        $otherCommenters = User::whereIn('id', 
            $task->comments()
                ->where('user_id', '!=', $comment->user_id)
                ->distinct()
                ->pluck('user_id')
        )->get();

        $usersToNotify = $usersToNotify->merge($otherCommenters)->unique('id');

        foreach ($usersToNotify as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'comment_added',
                'title' => 'Nuevo comentario',
                'message' => "{$comment->user->full_name} ha comentado en la tarea: {$task->title}",
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'comment_id' => $comment->id,
                    'comment_content' => substr($comment->content, 0, 100),
                    'commented_by' => $comment->user->full_name
                ]
            ]);

            $this->sendEmail($notification);
        }
    }

    public function sendDueDateReminderNotification(Task $task): void
    {
        if (!$task->assignedUser || !$task->due_date) {
            return;
        }

        $notification = Notification::create([
            'user_id' => $task->assigned_to,
            'type' => 'due_date_reminder',
            'title' => 'Recordatorio de vencimiento',
            'message' => "La tarea '{$task->title}' vence pronto",
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'due_date' => $task->due_date->format('d/m/Y H:i'),
                'board_title' => $task->column->board->title
            ]
        ]);

        $this->sendEmail($notification);
    }

    public function sendBoardInvitationNotification(User $user, $board, User $invitedBy): void
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'board_invitation',
            'title' => 'Invitación a tablero',
            'message' => "{$invitedBy->full_name} te ha invitado al tablero: {$board->title}",
            'data' => [
                'board_id' => $board->id,
                'board_title' => $board->title,
                'invited_by' => $invitedBy->full_name
            ]
        ]);

        $this->sendEmail($notification);
    }

    public function sendCitizenRequestNotification(User $user, $request): void
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'citizen_request_assigned',
            'title' => 'Nueva solicitud ciudadana asignada',
            'message' => "Se te ha asignado una nueva solicitud: {$request->subject}",
            'data' => [
                'request_id' => $request->id,
                'subject' => $request->subject,
                'category' => $request->category,
                'citizen_name' => $request->citizen_name
            ]
        ]);

        $this->sendEmail($notification);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
                                  ->where('user_id', $userId)
                                  ->first();

        if ($notification) {
            $notification->update(['is_read' => true]);
            return true;
        }

        return false;
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::where('user_id', $userId)
                   ->where('is_read', false)
                   ->update(['is_read' => true]);
    }

    public function getUnreadNotifications(int $userId, int $limit = 10): array
    {
        return Notification::where('user_id', $userId)
                          ->where('is_read', false)
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get()
                          ->toArray();
    }

    public function deleteOldNotifications(int $daysOld = 30): void
    {
        $cutoffDate = now()->subDays($daysOld);
        
        Notification::where('created_at', '<', $cutoffDate)
                   ->where('is_read', true)
                   ->delete();
    }

    private function sendEmail(Notification $notification): void
    {
        try {
            $user = User::find($notification->user_id);
            if (!$user || !$user->email) {
                return;
            }

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user->email, $user->full_name);
            $this->mailer->Subject = $notification->title;
            
            // Generar contenido HTML del email
            $emailContent = $this->generateEmailContent($notification);
            $this->mailer->msgHTML($emailContent);

            if ($this->mailer->send()) {
                $notification->update(['email_sent' => true]);
                $this->container->get('logger')->info("Email enviado a {$user->email} - {$notification->title}");
            }

        } catch (\Exception $e) {
            $this->container->get('logger')->error("Error enviando email: " . $e->getMessage());
        }
    }

    private function generateEmailContent(Notification $notification): string
    {
        $user = User::find($notification->user_id);
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$notification->title}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3498db; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Sistema Municipal San Francisco</h1>
                </div>
                <div class='content'>
                    <h2>{$notification->title}</h2>
                    <p>Hola {$user->full_name},</p>
                    <p>{$notification->message}</p>";

        // Agregar contenido específico según el tipo de notificación
        if ($notification->type === 'task_assigned' && isset($notification->data['task_id'])) {
            $html .= "<p><a href='{$appUrl}/dashboard/tasks/{$notification->data['task_id']}' class='btn'>Ver Tarea</a></p>";
        } elseif ($notification->type === 'board_invitation' && isset($notification->data['board_id'])) {
            $html .= "<p><a href='{$appUrl}/dashboard/boards/{$notification->data['board_id']}' class='btn'>Ver Tablero</a></p>";
        }

        $html .= "
                    <p>Puedes gestionar tus notificaciones desde el panel de control.</p>
                </div>
                <div class='footer'>
                    <p>Este es un email automático del Sistema Municipal de San Francisco.<br>
                    No responder a este email.</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }
}