<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReview extends Model
{
    protected $fillable = ['user_id', 'week_start', 'week_end', 'data', 'viewed_at'];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end'   => 'date',
            'data'       => 'array',
            'viewed_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }
}
