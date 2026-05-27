<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'household_id',
        'avatar',
        'is_active',
        'dashboard_cards',
        'consent_given_at',
        'consent_ip',
        'privacy_policy_version',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'dashboard_cards'    => 'array',
            'consent_given_at'   => 'datetime',
            'last_login_at'      => 'datetime',
        ];
    }

    /**
     * Relasi ke Household
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Relasi ke Transaksi
     */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    /**
     * Relasi ke Recurring Transaksi
     */
    public function recurringTransaksi(): HasMany
    {
        return $this->hasMany(RecurringTransaksi::class);
    }

    /**
     * Relasi ke Tabungan Transaksi
     */
    public function tabunganTransaksi(): HasMany
    {
        return $this->hasMany(TabunganTransaksi::class);
    }

    /**
     * Relasi ke Hutang Piutang Pembayaran
     */
    public function hutangPiutangPembayaran(): HasMany
    {
        return $this->hasMany(HutangPiutangPembayaran::class);
    }

    /**
     * Relasi ke Notifikasi
     */
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    /**
     * Relasi ke Laporan
     */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }

    /**
     * Relasi ke Audit Log
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Relasi ke OCR History
     */
    public function ocrHistory(): HasMany
    {
        return $this->hasMany(OcrHistory::class);
    }

    /**
     * Scope untuk user aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope berdasarkan household
     */
    public function scopeInHousehold($query, int $householdId)
    {
        return $query->where('household_id', $householdId);
    }
}
