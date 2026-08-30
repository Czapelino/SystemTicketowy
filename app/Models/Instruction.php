<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Instruction extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'instructions';

    protected $fillable = [
        'title',
        'content',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
