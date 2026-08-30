<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'messages';

    const ROLE_USER = 'user';
    const ROLE_AI = 'assistant';
    const ROLE_AGENT = 'agent';
    const ROLE_SYSTEM = 'system';

    const TYPE_CHAT = 'chat';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_ESCALATION = 'escalation';
    const TYPE_INTERNAL_NOTE = 'internal_note';

    protected $fillable = [
        'ticket_id',
        'role',
        'type',
        'content',
        'sender_name',
        'sender_id',
        'is_read',
        'ai_tokens_used',
        'ai_model',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'ai_tokens_used' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_read' => false,
        'type' => self::TYPE_CHAT,
        'metadata' => [],
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function scopeUserMessages($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    public function scopeAiMessages($query)
    {
        return $query->where('role', self::ROLE_AI);
    }

    public function isFromUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isFromAI(): bool
    {
        return $this->role === self::ROLE_AI;
    }

    public function isFromAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    public function getAvatarAttribute(): string
    {
        return match($this->role) {
            self::ROLE_USER => '👤',
            self::ROLE_AI => '🤖',
            self::ROLE_AGENT => '👩‍💼',
            default => '💬',
        };
    }
}
