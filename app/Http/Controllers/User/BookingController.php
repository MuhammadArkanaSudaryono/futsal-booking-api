<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(private BookingService $bookingService) {}

    // GET /api/bookings
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['field', 'payment'])
            ->where('user_id', $request->user()->id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 10);

        return $this->paginatedResponse($bookings);
    }

    // POST /api/bookings
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'field_id'     => 'required|exists:fields,id',
            'booking_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'slot_ids'     => 'required|array|min:1',
            'slot_ids.*'   => 'integer|exists:time_slots,id',
            'promo_code'   => 'nullable|string|max:30',
            'notes'        => 'nullable|string|max:500',
        ], [
            'field_id.required'       => 'Lapangan wajib dipilih.',
            'field_id.exists'         => 'Lapangan tidak ditemukan.',
            'booking_date.required'   => 'Tanggal booking wajib diisi.',
            'booking_date.after_or_equal' => 'Tanggal booking tidak boleh di masa lalu.',
            'slot_ids.required'       => 'Pilih minimal satu slot waktu.',
            'slot_ids.*.exists'       => 'Salah satu slot tidak valid.',
        ]);

        try {
            $booking = $this->bookingService->create([
                'user_id'      => $request->user()->id,
                'field_id'     => $request->field_id,
                'booking_date' => $request->booking_date,
                'slot_ids'     => $request->slot_ids,
                'promo_code'   => $request->promo_code,
                'notes'        => $request->notes,
            ]);

            return $this->createdResponse(
                $this->formatBooking($booking),
                'Booking berhasil dibuat. Silakan upload bukti pembayaran.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // GET /api/bookings/{booking}
    public function show(Request $request, Booking $booking): JsonResponse
    {
        // Pastikan booking milik user ini
        if ($booking->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $booking->load(['field.fieldType', 'details.timeSlot', 'payment', 'promotion']);

        return $this->successResponse($this->formatBooking($booking, detail: true));
    }

    // PUT /api/bookings/{booking}/cancel
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->bookingService->cancel(
                $booking,
                $request->user()->id,
                $request->reason ?? ''
            );

            return $this->successResponse(
                ['status' => $booking->status, 'status_label' => $booking->status_label],
                'Booking berhasil dibatalkan.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // POST /api/bookings/{booking}/payment
    public function uploadPayment(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $request->validate([
            'payment_proof'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'payment_method' => 'required|string|max:50',
        ], [
            'payment_proof.required' => 'Bukti pembayaran wajib diupload.',
            'payment_proof.mimes'    => 'Format file harus jpg, png, atau pdf.',
            'payment_proof.max'      => 'Ukuran file maksimal 2MB.',
            'payment_method.required'=> 'Metode pembayaran wajib diisi.',
        ]);

        try {
            $payment = $this->bookingService->uploadPayment(
                $booking,
                $request->file('payment_proof'),
                $request->payment_method
            );

            return $this->successResponse([
                'payment_status'  => $payment->payment_status,
                'status_label'    => $payment->status_label,
                'proof_url'       => $payment->proof_url,
            ], 'Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // ── Helper ─────────────────────────────────────────────────

    private function formatBooking(Booking $booking, bool $detail = false): array
    {
        $data = [
            'id'              => $booking->id,
            'booking_code'    => $booking->booking_code,
            'booking_date'    => $booking->booking_date?->format('Y-m-d'),
            'total_hours'     => (float) $booking->total_hours,
            'subtotal'        => (float) $booking->subtotal,
            'discount_amount' => (float) $booking->discount_amount,
            'total_amount'    => (float) $booking->total_amount,
            'status'          => $booking->status,
            'status_label'    => $booking->status_label,
            'notes'           => $booking->notes,
            'created_at'      => $booking->created_at,
            'field' => $booking->field ? [
                'id'   => $booking->field->id,
                'name' => $booking->field->name,
            ] : null,
            'payment' => $booking->payment ? [
                'payment_status' => $booking->payment->payment_status,
                'status_label'   => $booking->payment->status_label,
                'payment_method' => $booking->payment->payment_method,
                'proof_url'      => $booking->payment->proof_url,
                'amount'         => (float) $booking->payment->amount,
            ] : null,
        ];

        if ($detail) {
            $data['details'] = $booking->details->map(fn ($d) => [
                'start_time'     => $d->start_time,
                'end_time'       => $d->end_time,
                'price_per_hour' => (float) $d->price_per_hour,
            ]);
            $data['promotion'] = $booking->promotion ? [
                'code' => $booking->promotion->code,
                'name' => $booking->promotion->name,
            ] : null;
            $data['cancel_reason'] = $booking->cancel_reason;
        }

        return $data;
    }
}
