<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'tickets';

    const STATUS_OPEN = 'open';
    const STATUS_AI_HANDLING = 'ai_handling';
    const STATUS_ESCALATED = 'escalated';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'status',
        'priority',
        'category',
        'customer_name',
        'customer_email',
        'customer_phone',
        'assigned_to',
        'ai_summary',
        'ai_sentiment',
        'ai_suggested_category',
        'escalation_reason',
        'resolved_at',
        'resolution_notes',
        'satisfaction_rating',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'priority' => self::PRIORITY_MEDIUM,
        'tags' => [],
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'ticket_id');
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeEscalated($query)
    {
        return $query->where('status', self::STATUS_ESCALATED);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function isEscalated(): bool
    {
        return $this->status === self::STATUS_ESCALATED;
    }

    public function generateTicketNumber(): string
    {
        return 'TKT-' . strtoupper(uniqid());
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'TKT-' . strtoupper(substr(uniqid(), -8));
            }
        });
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'badge-info',
            self::STATUS_AI_HANDLING => 'badge-primary',
            self::STATUS_ESCALATED => 'badge-warning',
            self::STATUS_IN_PROGRESS => 'badge-secondary',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_CLOSED => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'badge-success',
            self::PRIORITY_MEDIUM => 'badge-info',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_URGENT => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
