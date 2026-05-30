<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'category',
        'tier_type', 'rarity', 'is_hidden', 'is_major',
        'xp_reward', 'condition_type', 'condition_value',
    ];

    protected function casts(): array
    {
        return [
            'condition_value' => 'array',
            'is_hidden'       => 'boolean',
            'is_major'        => 'boolean',
        ];
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
