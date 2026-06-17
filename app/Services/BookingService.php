<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Field;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\TimeSlot;
use App\Traits\GeneratesBookingCode;
use Illuminate\Support\Facades\DB;

class BookingService
{
    use GeneratesBookingCode;

    public function __construct(
        private AvailabilityService $availabilityService,
        private PromotionService    $promotionService,
        private FileUploadService   $fileUploadService,
    ) {}

    // ── Buat Booking Baru ──────────────────────────────────────

    /**
     * @param  array  $data {
     *   user_id, field_id, booking_date,
     *   slot_ids: int[],
     *   promo_code?: string,
     *   notes?: string
     * }
     * @throws \Exception
     */
    public function create(array $data): Booking
    {
        $field    = Field::findOrFail($data['field_id']);
        $slotIds  = $data['slot_ids'];
        $date     = $data['booking_date'];

        // Validasi slots milik lapangan ini
        $slots = TimeSlot::whereIn('id', $slotIds)
            ->where('field_id', $field->id)
            ->where('is_active', true)
            ->get();

        if ($slots->count() !== count($slotIds)) {
            throw new \Exception('Beberapa slot tidak valid atau tidak aktif.');
        }

        // Hitung subtotal
        $totalHours = collect($slots)->sum(fn ($s) => $s->duration_hours);
        $subtotal   = round($totalHours * (float) $field->price_per_hour, 2);

        // Validasi & hitung promo
        $promotionId    = null;
        $discountAmount = 0;

        if (! empty($data['promo_code'])) {
            $promoResult = $this->promotionService->validateCode($data['promo_code'], $subtotal);

            if (! $promoResult['valid']) {
                throw new \Exception($promoResult['message']);
            }

            $promotionId    = $promoResult['promotion']->id;
            $discountAmount = $promoResult['discount'];
        }

        $totalAmount = max(0, $subtotal - $discountAmount);

        // ── DATABASE TRANSACTION ───────────────────────────────
        return DB::transaction(function () use (
            $field, $slots, $slotIds, $date, $data,
            $subtotal, $discountAmount, $totalAmount, $totalHours, $promotionId
        ) {
            // 1. Re-check ketersediaan (cegah race condition / double booking)
            if (! $this->availabilityService->areSlotsAvailable($slotIds, $date)) {
                throw new \Exception('Slot yang dipilih sudah tidak tersedia. Silakan pilih jam lain.');
            }

            // 2. Buat booking
            $booking = Booking::create([
                'booking_code'    => $this->generateBookingCode(),
                'user_id'         => $data['user_id'],
                'field_id'        => $field->id,
                'promotion_id'    => $promotionId,
                'booking_date'    => $date,
                'total_hours'     => $totalHours,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount'    => $totalAmount,
                'status'          => Booking::STATUS_PENDING,
                'notes'           => $data['notes'] ?? null,
            ]);

            // 3. Buat booking details (satu baris per slot)
            foreach ($slots as $slot) {
                BookingDetail::create([
                    'booking_id'     => $booking->id,
                    'time_slot_id'   => $slot->id,
                    'start_time'     => $slot->start_time,
                    'end_time'       => $slot->end_time,
                    'price_per_hour' => $field->price_per_hour,
                ]);
            }

            // 4. Buat payment record awal
            Payment::create([
                'booking_id'     => $booking->id,
                'amount'         => $totalAmount,
                'payment_status' => Payment::STATUS_UNPAID,
            ]);

            // 5. Increment used_count promo jika ada
            if ($promotionId) {
                Promotion::where('id', $promotionId)->increment('used_count');
            }

            return $booking->load(['details.timeSlot', 'payment', 'field', 'promotion']);
        });
    }

    // ── Upload Bukti Pembayaran ────────────────────────────────

    public function uploadPayment(Booking $booking, $file, string $paymentMethod): Payment
    {
        if (! $booking->isPending()) {
            throw new \Exception('Booking tidak dapat diubah pada status saat ini.');
        }

        $payment = $booking->payment;

        if (! $payment) {
            throw new \Exception('Data pembayaran tidak ditemukan.');
        }

        $path = $this->fileUploadService->replace(
            $file,
            $payment->payment_proof,
            'payments'
        );

        $payment->update([
            'payment_method' => $paymentMethod,
            'payment_proof'  => $path,
            'payment_status' => Payment::STATUS_PENDING_VERIFICATION,
            'paid_at'        => now(),
        ]);

        return $payment->fresh();
    }

    // ── Konfirmasi Booking (Admin) ─────────────────────────────

    public function confirm(Booking $booking, int $adminId): Booking
    {
        if (! $booking->isPending()) {
            throw new \Exception('Hanya booking dengan status pending yang bisa dikonfirmasi.');
        }

        DB::transaction(function () use ($booking, $adminId) {
            $booking->update(['status' => Booking::STATUS_CONFIRMED]);

            $booking->payment()->update([
                'payment_status' => Payment::STATUS_VERIFIED,
                'verified_at'    => now(),
                'verified_by'    => $adminId,
            ]);
        });

        return $booking->fresh(['details.timeSlot', 'payment', 'field', 'user']);
    }

    // ── Tolak Booking (Admin) ──────────────────────────────────

    public function reject(Booking $booking, string $reason): Booking
    {
        if (! $booking->isPending()) {
            throw new \Exception('Hanya booking dengan status pending yang bisa ditolak.');
        }

        DB::transaction(function () use ($booking, $reason) {
            $booking->update([
                'status'        => Booking::STATUS_REJECTED,
                'cancel_reason' => $reason,
                'cancelled_at'  => now(),
            ]);

            $booking->payment()->update([
                'payment_status' => Payment::STATUS_REFUNDED,
            ]);
        });

        return $booking->fresh();
    }

    // ── Batalkan Booking (User) ────────────────────────────────

    public function cancel(Booking $booking, int $userId, string $reason = ''): Booking
    {
        if ($booking->user_id !== $userId) {
            throw new \Exception('Anda tidak berhak membatalkan booking ini.');
        }

        if (! $booking->isCancellable()) {
            throw new \Exception('Booking hanya bisa dibatalkan saat masih berstatus pending.');
        }

        DB::transaction(function () use ($booking, $reason) {
            $booking->update([
                'status'        => Booking::STATUS_CANCELLED,
                'cancelled_at'  => now(),
                'cancel_reason' => $reason,
            ]);

            // Kembalikan kuota promo jika ada
            if ($booking->promotion_id) {
                Promotion::where('id', $booking->promotion_id)->decrement('used_count');
            }
        });

        return $booking->fresh();
    }
}
