<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'household_id',
        'action',
        'model',
        'status_code',
        'success',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public const ACTIONS = [
        'ocr'              => 'OCR Struk',
        'suggest_detail'   => 'Suggest Detail',
        'generate_insight' => 'Insight Keuangan',
        'detect_anomaly'   => 'Deteksi Anomali',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
