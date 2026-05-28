<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGamification extends Model
{
    protected $table = 'user_gamification';

    protected $fillable = [
        'user_id',
        'total_xp',
        'level',
        'momentum_score',
        'last_active_date',
        'inactive_days_count',
        'grace_days_used',
        'grace_period_start',
    ];

    protected function casts(): array
    {
        return [
            'last_active_date'   => 'date',
            'grace_period_start' => 'date',
            'momentum_score'     => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
