<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    // GET /api/admin/users
    public function index(Request $request): JsonResponse
    {
        $users = User::where('role', 'user')
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
            )
            ->when(isset($request->is_active), fn ($q) =>
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->withCount('bookings')
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($users);
    }

    // GET /api/admin/users/{user}
    public function show(User $user): JsonResponse
    {
        $user->loadCount('bookings');

        $recentBookings = $user->bookings()
            ->with('field:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'booking_code', 'booking_date', 'total_amount', 'status', 'field_id']);

        return $this->successResponse([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'avatar_url'      => $user->avatar_url,
            'role'            => $user->role,
            'is_active'       => $user->is_active,
            'bookings_count'  => $user->bookings_count,
            'recent_bookings' => $recentBookings,
            'created_at'      => $user->created_at,
        ]);
    }

    // PUT /api/admin/users/{user}/toggle-status
    public function toggleStatus(User $user): JsonResponse
    {
        if ($user->role === 'admin') {
            return $this->errorResponse('Status akun admin tidak bisa diubah dari sini.', 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->successResponse([
            'id'        => $user->id,
            'name'      => $user->name,
            'is_active' => $user->is_active,
        ], "Akun {$user->name} berhasil {$status}.");
    }
}
