<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldImage extends Model
{
    public $timestamps = false; // Hanya punya created_at

    protected $fillable = [
        'field_id',
        'image_path',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

    // ── Relationships ──────────────────────────────────────────

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
