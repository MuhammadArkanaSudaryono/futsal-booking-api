<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_booking',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_booking'    => 'decimal:2',
            'is_active'      => 'boolean',
            'valid_from'     => 'date',
            'valid_until'    => 'date',
        ];
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('valid_from', '<=', today())
                     ->where('valid_until', '>=', today());
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Cek apakah promo valid untuk digunakan.
     * Mengembalikan array ['valid' => bool, 'message' => string]
     */
    public function validate(float $bookingTotal): array
    {
        if (! $this->is_active) {
            return ['valid' => false, 'message' => 'Promo tidak aktif.'];
        }

        if (today()->lt($this->valid_from) || today()->gt($this->valid_until)) {
            return ['valid' => false, 'message' => 'Promo sudah tidak berlaku.'];
        }

        if ($bookingTotal < (float) $this->min_booking) {
            return [
                'valid'   => false,
                'message' => 'Minimal booking Rp ' . number_format($this->min_booking, 0, ',', '.') . ' untuk menggunakan promo ini.',
            ];
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return ['valid' => false, 'message' => 'Kuota promo sudah habis.'];
        }

        return ['valid' => true, 'message' => 'Promo valid.'];
    }

    /**
     * Hitung nominal diskon berdasarkan total booking.
     */
    public function calculateDiscount(float $bookingTotal): float
    {
        if ($this->discount_type === 'percent') {
            return round($bookingTotal * ((float) $this->discount_value / 100), 2);
        }

        // Fixed: diskon tidak boleh melebihi total booking
        return min((float) $this->discount_value, $bookingTotal);
    }

    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->max_uses === null) {
            return null; // unlimited
        }
        return max(0, $this->max_uses - $this->used_count);
    }

    // ── Relationships ──────────────────────────────────────────

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
