<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    protected $fillable = [
        'slug', 'type', 'category', 'title', 'description',
        'difficulty', 'xp_reward', 'momentum_bonus', 'condition_type', 'condition_value',
    ];

    protected function casts(): array
    {
        return [
            'condition_value' => 'array',
        ];
    }

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserChallenge::class);
    }
}
