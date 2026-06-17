<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\TimeSlot;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    use ApiResponse;

    // GET /api/admin/fields/{field}/time-slots
    public function index(Field $field): JsonResponse
    {
        $slots = $field->timeSlots()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'day_of_week'    => $s->day_of_week,
                'day_name'       => $s->day_name,
                'start_time'     => $s->start_time,
                'end_time'       => $s->end_time,
                'duration_hours' => $s->duration_hours,
                'is_active'      => $s->is_active,
            ]);

        return $this->successResponse([
            'field_id'   => $field->id,
            'field_name' => $field->name,
            'slots'      => $slots,
        ]);
    }

    // POST /api/admin/fields/{field}/time-slots
    public function store(Request $request, Field $field): JsonResponse
    {
        $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'is_active'   => 'sometimes|boolean',
        ], [
            'day_of_week.between' => 'Hari harus antara 0 (Minggu) sampai 6 (Sabtu).',
            'end_time.after'      => 'Jam selesai harus setelah jam mulai.',
        ]);

        // Cek duplikat
        $exists = TimeSlot::where('field_id', $field->id)
            ->where('day_of_week', $request->day_of_week)
            ->where('start_time', $request->start_time)
            ->exists();

        if ($exists) {
            return $this->errorResponse('Slot waktu dengan hari dan jam mulai yang sama sudah ada.', 422);
        }

        $slot = TimeSlot::create([
            'field_id'    => $field->id,
            'day_of_week' => $request->day_of_week,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return $this->createdResponse([
            'id'             => $slot->id,
            'day_name'       => $slot->day_name,
            'start_time'     => $slot->start_time,
            'end_time'       => $slot->end_time,
            'duration_hours' => $slot->duration_hours,
            'is_active'      => $slot->is_active,
        ], 'Slot waktu berhasil ditambahkan.');
    }

    // PUT /api/admin/time-slots/{timeSlot}
    public function update(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        $request->validate([
            'start_time' => 'sometimes|date_format:H:i',
            'end_time'   => 'sometimes|date_format:H:i|after:start_time',
            'is_active'  => 'sometimes|boolean',
        ]);

        $timeSlot->update($request->only(['start_time', 'end_time', 'is_active']));

        return $this->successResponse([
            'id'          => $timeSlot->id,
            'day_name'    => $timeSlot->day_name,
            'start_time'  => $timeSlot->start_time,
            'end_time'    => $timeSlot->end_time,
            'is_active'   => $timeSlot->is_active,
        ], 'Slot waktu berhasil diperbarui.');
    }

    // DELETE /api/admin/time-slots/{timeSlot}
    public function destroy(TimeSlot $timeSlot): JsonResponse
    {
        // Cegah hapus jika slot pernah dipakai di booking aktif
        $hasBooking = $timeSlot->bookingDetails()
            ->whereHas('booking', fn ($q) =>
                $q->whereNotIn('status', ['cancelled', 'rejected'])
            )->exists();

        if ($hasBooking) {
            return $this->errorResponse(
                'Slot tidak bisa dihapus karena masih digunakan di booking aktif.',
                422
            );
        }

        $timeSlot->delete();

        return $this->successResponse(null, 'Slot waktu berhasil dihapus.');
    }
}
