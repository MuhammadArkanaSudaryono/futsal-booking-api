<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BookingsExport;

class ReportController extends Controller
{
    use ApiResponse;

    // GET /api/admin/reports/bookings
    public function bookings(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date|date_format:Y-m-d',
            'date_to'   => 'nullable|date|date_format:Y-m-d|after_or_equal:date_from',
            'status'    => 'nullable|in:pending,confirmed,rejected,completed,cancelled',
        ]);

        $bookings = Booking::with(['user:id,name,email', 'field:id,name', 'payment'])
            ->when($request->date_from, fn ($q) => $q->whereDate('booking_date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->whereDate('booking_date', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('booking_date')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($bookings);
    }

    // GET /api/admin/reports/revenue
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2099',
        ]);

        $year = $request->year ?? now()->year;

        $monthly = Booking::selectRaw('
                MONTH(booking_date) as month,
                COUNT(*) as total_bookings,
                SUM(total_amount) as total_revenue,
                SUM(CASE WHEN status IN ("confirmed","completed") THEN total_amount ELSE 0 END) as confirmed_revenue
            ')
            ->whereYear('booking_date', $year)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->groupByRaw('MONTH(booking_date)')
            ->orderByRaw('MONTH(booking_date)')
            ->get();

        return $this->successResponse([
            'year'    => $year,
            'monthly' => $monthly,
        ]);
    }

    // GET /api/admin/export/pdf
    public function exportPdf(Request $request): Response
    {
        $request->validate([
            'date_from' => 'nullable|date|date_format:Y-m-d',
            'date_to'   => 'nullable|date|date_format:Y-m-d',
            'status'    => 'nullable|string',
        ]);

        $bookings = Booking::with(['user:id,name,email', 'field:id,name', 'payment'])
            ->when($request->date_from, fn ($q) => $q->whereDate('booking_date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->whereDate('booking_date', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('booking_date')
            ->get();

        $pdf = Pdf::loadView('exports.bookings-pdf', [
            'bookings'  => $bookings,
            'date_from' => $request->date_from,
            'date_to'   => $request->date_to,
            'generated' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan-booking-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    // GET /api/admin/export/excel
    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $request->validate([
            'date_from' => 'nullable|date|date_format:Y-m-d',
            'date_to'   => 'nullable|date|date_format:Y-m-d',
            'status'    => 'nullable|string',
        ]);

        $filename = 'laporan-booking-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new BookingsExport(
                $request->date_from,
                $request->date_to,
                $request->status
            ),
            $filename
        );
    }
}
