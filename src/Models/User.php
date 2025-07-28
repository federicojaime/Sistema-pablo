<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'username', 'email', 'password_hash', 'full_name', 
        'department', 'role', 'avatar', 'is_active'
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relaciones
    public function ownedBoards()
    {
        return $this->hasMany(Board::class, 'owner_id');
    }

    public function boards()
    {
        return $this->belongsToMany(Board::class, 'board_members', 'user_id', 'board_id')
                    ->withPivot('role', 'joined_at');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function citizenRequests()
    {
        return $this->hasMany(CitizenRequest::class, 'assigned_to');
    }

    // Métodos de utilidad
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    public function getUnreadMessagesCount(): int
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    public function toArray()
    {
        $array = parent::toArray();
        unset($array['password_hash']);
        return $array;
    }
}