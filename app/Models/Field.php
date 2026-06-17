<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_type_id',
        'name',
        'description',
        'price_per_hour',
        'facilities',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'facilities'     => 'array',
            'price_per_hour' => 'decimal:2',
        ];
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();

        return $primary ? asset('storage/' . $primary->image_path) : null;
    }

    // ── Relationships ──────────────────────────────────────────

    public function fieldType(): BelongsTo
    {
        return $this->belongsTo(FieldType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(FieldImage::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
