<?php

namespace App\Models;

use App\Traits\HasSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory, HasSoftDelete;

    protected $fillable = [
        'nama',
        'slug',
        'plan_id',
        'subscription_start',
        'subscription_end',
        'renewal_reminded_at',
        'status',
    ];

    protected $casts = [
        'subscription_start'  => 'date',
        'subscription_end'    => 'date',
        'renewal_reminded_at' => 'datetime',
    ];

    /**
     * Relasi ke Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Relasi ke Users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relasi ke Transaksi
     */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    /**
     * Relasi ke Kategori
     */
    public function kategori(): HasMany
    {
        return $this->hasMany(Kategori::class);
    }

    /**
     * Relasi ke Sumber Transaksi
     */
    public function sumberTransaksi(): HasMany
    {
        return $this->hasMany(SumberTransaksi::class);
    }

    /**
     * Relasi ke Anggaran
     */
    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class);
    }

    /**
     * Relasi ke Tabungan
     */
    public function tabungan(): HasMany
    {
        return $this->hasMany(Tabungan::class);
    }

    /**
     * Relasi ke Hutang Piutang
     */
    public function hutangPiutang(): HasMany
    {
        return $this->hasMany(HutangPiutang::class);
    }

    /**
     * Relasi ke Settings
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Check apakah subscription masih aktif
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->subscription_end) {
            return true;
        }

        return $this->subscription_end->isFuture();
    }

    /**
     * Scope untuk household aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
