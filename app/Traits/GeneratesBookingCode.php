<?php

namespace App\Traits;

use App\Models\Booking;

trait GeneratesBookingCode
{
    /**
     * Generate kode booking unik.
     * Format: FK-YYYYMMDD-XXXX (contoh: FK-20240601-0023)
     */
    protected function generateBookingCode(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "FK-{$date}-";

        // Hitung booking yang sudah ada hari ini
        $todayCount = Booking::whereDate('created_at', today())->count();
        $sequence   = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        $code = $prefix . $sequence;

        // Pastikan tidak duplikat (edge case)
        while (Booking::where('booking_code', $code)->exists()) {
            $sequence = str_pad((int) $sequence + 1, 4, '0', STR_PAD_LEFT);
            $code     = $prefix . $sequence;
        }

        return $code;
    }
}
