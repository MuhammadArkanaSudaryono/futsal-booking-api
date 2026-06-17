<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDetail extends Model
{
    public $timestamps = false; // Hanya punya created_at

    protected $fillable = [
        'booking_id',
        'time_slot_id',
        'start_time',
        'end_time',
        'price_per_hour',
    ];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'created_at'     => 'datetime',
        ];
    }

    // ── Helpers ────────────────────────────────────────────────

    /** Hitung biaya satu detail (harga × durasi jam) */
    public function getSubtotalAttribute(): float
    {
        [$sh, $sm] = explode(':', $this->start_time);
        [$eh, $em] = explode(':', $this->end_time);

        $durationHours = ((int) $eh * 60 + (int) $em - ((int) $sh * 60 + (int) $sm)) / 60;

        return round((float) $this->price_per_hour * $durationHours, 2);
    }

    // ── Relationships ──────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
