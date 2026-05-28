<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChallenge extends Model
{
    protected $fillable = [
        'user_id', 'challenge_id', 'progress',
        'started_at', 'expires_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress'     => 'array',
            'started_at'   => 'datetime',
            'expires_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->completed_at === null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
