<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Modelo Board
class Board extends Model
{
    protected $fillable = ['title', 'description', 'color', 'owner_id', 'is_public'];
    protected $casts = ['is_public' => 'boolean'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'board_members', 'board_id', 'user_id')
                    ->withPivot('role', 'joined_at');
    }

    public function columns()
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function tasks()
    {
        return $this->hasManyThrough(Task::class, Column::class);
    }
}

// Modelo Column
class Column extends Model
{
    protected $fillable = ['board_id', 'title', 'position', 'color'];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}

// Modelo Task
class Task extends Model
{
    protected $fillable = [
        'column_id', 'title', 'description', 'priority', 'status',
        'assigned_to', 'created_by', 'due_date', 'position', 'tags', 'attachments'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'tags' => 'array',
        'attachments' => 'array'
    ];

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }
}

// Modelo Comment
class Comment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'content'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Modelo Notification
class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'data', 'is_read', 'email_sent'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'email_sent' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Modelo CitizenRequest
class CitizenRequest extends Model
{
    protected $fillable = [
        'citizen_id', 'citizen_name', 'citizen_email', 'citizen_phone', 'citizen_dni',
        'subject', 'description', 'category', 'priority', 'status', 'assigned_to',
        'response', 'attachments'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'request_id');
    }
}

// Modelo Message
class Message extends Model
{
    protected $fillable = [
        'sender_id', 'receiver_id', 'board_id', 'task_id', 
        'content', 'message_type', 'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}

// Modelo File
class File extends Model
{
    protected $fillable = [
        'filename', 'original_name', 'file_path', 'file_size',
        'mime_type', 'uploaded_by', 'task_id', 'request_id'
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function citizenRequest()
    {
        return $this->belongsTo(CitizenRequest::class, 'request_id');
    }
}

// Modelo ActivityLog
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id', 
        'details', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'details' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Modelo BoardMember
class BoardMember extends Model
{
    protected $fillable = ['board_id', 'user_id', 'role'];
    protected $casts = ['joined_at' => 'datetime'];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}