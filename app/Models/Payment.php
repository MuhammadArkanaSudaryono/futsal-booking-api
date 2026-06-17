<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'payment_method',
        'payment_proof',
        'amount',
        'payment_status',
        'paid_at',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'paid_at'     => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // ── Status Constants ───────────────────────────────────────

    const STATUS_UNPAID               = 'unpaid';
    const STATUS_PENDING_VERIFICATION = 'pending_verification';
    const STATUS_VERIFIED             = 'verified';
    const STATUS_REFUNDED             = 'refunded';

    // ── Helpers ────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->payment_status === self::STATUS_VERIFIED;
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->payment_proof
            ? asset('storage/' . $this->payment_proof)
            : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::STATUS_UNPAID               => 'Belum Bayar',
            self::STATUS_PENDING_VERIFICATION => 'Menunggu Verifikasi',
            self::STATUS_VERIFIED             => 'Terverifikasi',
            self::STATUS_REFUNDED             => 'Dikembalikan',
            default                           => ucfirst($this->payment_status),
        };
    }

    // ── Relationships ──────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Admin yang memverifikasi pembayaran */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
