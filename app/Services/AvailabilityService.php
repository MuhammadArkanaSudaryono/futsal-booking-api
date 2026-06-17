<?php

namespace App\Services;

use App\Models\BookingDetail;
use App\Models\Field;
use App\Models\TimeSlot;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Ambil semua slot untuk lapangan pada tanggal tertentu,
     * lengkap dengan status available/booked.
     *
     * @param  Field   $field
     * @param  string  $date   Format: Y-m-d
     * @return array
     */
    public function getSlots(Field $field, string $date): array
    {
        $carbon     = Carbon::parse($date);
        $dayOfWeek  = $carbon->dayOfWeek; // 0=Minggu ... 6=Sabtu

        // Ambil semua slot aktif lapangan ini pada hari tersebut
        $timeSlots = TimeSlot::where('field_id', $field->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($timeSlots->isEmpty()) {
            return [];
        }

        // Kumpulkan time_slot_id yang sudah dipesan pada tanggal itu
        $bookedSlotIds = BookingDetail::whereIn('time_slot_id', $timeSlots->pluck('id'))
            ->whereHas('booking', function ($q) use ($date) {
                $q->whereDate('booking_date', $date)
                  ->whereNotIn('status', ['cancelled', 'rejected']);
            })
            ->pluck('time_slot_id')
            ->toArray();

        // Susun hasil dengan status
        return $timeSlots->map(function (TimeSlot $slot) use ($bookedSlotIds, $field) {
            $isBooked = in_array($slot->id, $bookedSlotIds);

            return [
                'id'             => $slot->id,
                'day_of_week'    => $slot->day_of_week,
                'day_name'       => $slot->day_name,
                'start_time'     => $slot->start_time,
                'end_time'       => $slot->end_time,
                'duration_hours' => $slot->duration_hours,
                'price_per_hour' => (float) $field->price_per_hour,
                'slot_price'     => round((float) $field->price_per_hour * $slot->duration_hours, 2),
                'is_available'   => ! $isBooked,
            ];
        })->toArray();
    }

    /**
     * Cek apakah sekumpulan slot_id tersedia pada tanggal tertentu.
     * Dipakai saat proses booking untuk cegah race condition.
     *
     * @param  array   $slotIds
     * @param  string  $date
     * @return bool
     */
    public function areSlotsAvailable(array $slotIds, string $date): bool
    {
        $booked = BookingDetail::whereIn('time_slot_id', $slotIds)
            ->whereHas('booking', function ($q) use ($date) {
                $q->whereDate('booking_date', $date)
                  ->whereNotIn('status', ['cancelled', 'rejected']);
            })
            ->lockForUpdate()
            ->exists();

        return ! $booked;
    }
}
