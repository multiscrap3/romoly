<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHistory extends Model
{
    use HasFactory;

    protected $table = 'payment_history';

    protected $fillable = [
        'household_id',
        'plan_id',
        'gateway',
        'merchant_ref',
        'amount',
        'billing_period',
        'payment_method',
        'payment_channel',
        'payment_reference',
        'checkout_url',
        'status',
        'payment_date',
        'expired_at',
        'period_start',
        'period_end',
        'notes',
        'raw_payload',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'expired_at'   => 'datetime',
        'period_start' => 'date',
        'period_end'   => 'date',
        'raw_payload'  => 'array',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
