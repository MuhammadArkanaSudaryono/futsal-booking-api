<?php

namespace App\Http\Controllers\Admin;

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

    // GET /api/admin/bookings
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['user:id,name,email,phone', 'field:id,name', 'payment'])
            ->when($request->status,       fn ($q) => $q->where('status', $request->status))
            ->when($request->field_id,     fn ($q) => $q->where('field_id', $request->field_id))
            ->when($request->date_from,    fn ($q) => $q->whereDate('booking_date', '>=', $request->date_from))
            ->when($request->date_to,      fn ($q) => $q->whereDate('booking_date', '<=', $request->date_to))
            ->when($request->search, function ($q) use ($request) {
                $q->where('booking_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('name', 'like', '%' . $request->search . '%')
                  );
            })
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($bookings);
    }

    // GET /api/admin/bookings/{booking}
    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'user:id,name,email,phone',
            'field.fieldType',
            'field.images',
            'details.timeSlot',
            'payment.verifier:id,name',
            'promotion',
        ]);

        return $this->successResponse($booking);
    }

    // PUT /api/admin/bookings/{booking}/confirm
    public function confirm(Request $request, Booking $booking): JsonResponse
    {
        try {
            $booking = $this->bookingService->confirm($booking, $request->user()->id);

            return $this->successResponse([
                'booking_code'   => $booking->booking_code,
                'status'         => $booking->status,
                'status_label'   => $booking->status_label,
                'payment_status' => $booking->payment?->payment_status,
            ], 'Booking berhasil dikonfirmasi.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // PUT /api/admin/bookings/{booking}/reject
    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        try {
            $booking = $this->bookingService->reject($booking, $request->reason);

            return $this->successResponse([
                'booking_code' => $booking->booking_code,
                'status'       => $booking->status,
                'status_label' => $booking->status_label,
                'cancel_reason'=> $booking->cancel_reason,
            ], 'Booking berhasil ditolak.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // POST /api/admin/bookings — Booking manual (walk-in)
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'field_id'     => 'required|exists:fields,id',
            'booking_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'slot_ids'     => 'required|array|min:1',
            'slot_ids.*'   => 'integer|exists:time_slots,id',
            'promo_code'   => 'nullable|string',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->bookingService->create([
                'user_id'      => $request->user_id,
                'field_id'     => $request->field_id,
                'booking_date' => $request->booking_date,
                'slot_ids'     => $request->slot_ids,
                'promo_code'   => $request->promo_code,
                'notes'        => $request->notes,
            ]);

            // Auto-confirm booking manual
            $booking = $this->bookingService->confirm($booking, $request->user()->id);

            return $this->createdResponse($booking, 'Booking manual berhasil dibuat dan dikonfirmasi.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
