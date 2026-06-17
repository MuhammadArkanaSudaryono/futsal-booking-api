<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Field;
use App\Models\Payment;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    // GET /api/admin/dashboard
    public function index(): JsonResponse
    {
        // Ringkasan booking
        $bookingSummary = Booking::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Total pendapatan (dari booking confirmed/completed)
        $totalRevenue = Payment::whereHas('booking', fn ($q) =>
                $q->whereIn('status', ['confirmed', 'completed'])
            )
            ->where('payment_status', 'verified')
            ->sum('amount');

        // Pendapatan bulan ini
        $monthlyRevenue = Payment::whereHas('booking', fn ($q) =>
                $q->whereIn('status', ['confirmed', 'completed'])
            )
            ->where('payment_status', 'verified')
            ->whereMonth('verified_at', now()->month)
            ->whereYear('verified_at', now()->year)
            ->sum('amount');

        // Booking pending (perlu tindakan)
        $pendingCount = Booking::where('status', 'pending')->count();

        // Total user aktif
        $totalUsers = User::where('role', 'user')->where('is_active', true)->count();

        // Total lapangan aktif
        $totalFields = Field::where('status', 'active')->count();

        // Lapangan terpopuler (top 5)
        $popularFields = Booking::select('field_id', DB::raw('COUNT(*) as booking_count'))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->with('field:id,name')
            ->groupBy('field_id')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'field_id'      => $b->field_id,
                'field_name'    => $b->field?->name,
                'booking_count' => $b->booking_count,
            ]);

        // Pendapatan 6 bulan terakhir (untuk grafik)
        $revenueChart = Payment::select(
                DB::raw('YEAR(verified_at) as year'),
                DB::raw('MONTH(verified_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('payment_status', 'verified')
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['confirmed', 'completed']))
            ->where('verified_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get()
            ->map(fn ($r) => [
                'month' => sprintf('%d-%02d', $r->year, $r->month),
                'total' => (float) $r->total,
            ]);

        return $this->successResponse([
            'bookings' => [
                'total'   => Booking::count(),
                'pending' => $bookingSummary['pending']   ?? 0,
                'confirmed'=> $bookingSummary['confirmed'] ?? 0,
                'completed'=> $bookingSummary['completed'] ?? 0,
                'cancelled'=> $bookingSummary['cancelled'] ?? 0,
                'rejected' => $bookingSummary['rejected']  ?? 0,
            ],
            'revenue' => [
                'total'   => (float) $totalRevenue,
                'monthly' => (float) $monthlyRevenue,
            ],
            'counts' => [
                'pending_actions' => $pendingCount,
                'active_users'    => $totalUsers,
                'active_fields'   => $totalFields,
            ],
            'popular_fields' => $popularFields,
            'revenue_chart'  => $revenueChart,
        ]);
    }
}
