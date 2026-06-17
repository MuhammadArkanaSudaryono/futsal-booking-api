<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    public $timestamps = false; // Hanya punya created_at

    protected $fillable = [
        'field_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'day_of_week' => 'integer',
            'created_at'  => 'datetime',
        ];
    }

    // ── Helpers ────────────────────────────────────────────────

    /** Nama hari dalam Bahasa Indonesia */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /** Hitung durasi slot dalam jam */
    public function getDurationHoursAttribute(): float
    {
        [$startH, $startM] = explode(':', $this->start_time);
        [$endH,   $endM]   = explode(':', $this->end_time);

        $startMinutes = (int) $startH * 60 + (int) $startM;
        $endMinutes   = (int) $endH   * 60 + (int) $endM;

        return ($endMinutes - $startMinutes) / 60;
    }

    // ── Relationships ──────────────────────────────────────────

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function bookingDetails(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }
}
