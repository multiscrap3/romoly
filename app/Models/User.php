<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'household_id',
        'avatar',
        'is_active',
        'dashboard_cards',
        'tour_progress',
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
            'tour_progress'      => 'array',
            'consent_given_at'   => 'datetime',
            'last_login_at'      => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Guided Tour / User Guide (kolom JSON tour_progress)
    |--------------------------------------------------------------------------
    */

    /**
     * Apakah user sudah pernah melihat tour untuk key tertentu (nama route / 'welcome').
     */
    public function hasSeenTour(string $key): bool
    {
        if ($key === 'welcome') {
            return (bool) ($this->tour_progress['welcome_completed'] ?? false);
        }

        return in_array($key, $this->tour_progress['seen'] ?? [], true);
    }

    /**
     * Tandai sebuah tour halaman sudah dilihat/dilewati.
     */
    public function markTourSeen(string $key): void
    {
        $progress = $this->tour_progress ?? [];
        $seen     = $progress['seen'] ?? [];

        if (! in_array($key, $seen, true)) {
            $seen[] = $key;
        }

        $progress['seen']       = array_values($seen);
        $progress['updated_at'] = now()->toIso8601String();

        $this->tour_progress = $progress;
        $this->save();
    }

    /**
     * Tandai tour global "welcome" sudah selesai.
     */
    public function markWelcomeTourCompleted(): void
    {
        $progress = $this->tour_progress ?? [];
        $progress['welcome_completed'] = true;
        $progress['updated_at']        = now()->toIso8601String();

        $this->tour_progress = $progress;
        $this->save();
    }

    /**
     * Reset seluruh progress tour (untuk fitur "Putar ulang panduan").
     */
    public function resetTour(): void
    {
        $this->tour_progress = null;
        $this->save();
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

    public function gamification(): HasOne
    {
        return $this->hasOne(UserGamification::class);
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
