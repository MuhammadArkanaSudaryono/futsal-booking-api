<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $promos = Promotion::when($request->is_active !== null, fn ($q) =>
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 10);

        return $this->paginatedResponse($promos);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'           => 'required|string|max:30|unique:promotions,code',
            'name'           => 'required|string|max:100',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:1',
            'min_booking'    => 'nullable|numeric|min:0',
            'max_uses'       => 'nullable|integer|min:1',
            'valid_from'     => 'required|date|date_format:Y-m-d',
            'valid_until'    => 'required|date|date_format:Y-m-d|after_or_equal:valid_from',
            'is_active'      => 'sometimes|boolean',
        ], [
            'code.unique'               => 'Kode promo sudah digunakan.',
            'discount_type.in'          => 'Tipe diskon harus percent atau fixed.',
            'valid_until.after_or_equal'=> 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        ]);

        // Uppercase kode promo
        $data         = $request->all();
        $data['code'] = strtoupper($data['code']);

        $promo = Promotion::create($data);

        return $this->createdResponse($promo, 'Promo berhasil ditambahkan.');
    }

    public function show(Promotion $promotion): JsonResponse
    {
        $promotion->loadCount('bookings');

        return $this->successResponse([
            ...$promotion->toArray(),
            'remaining_uses' => $promotion->remaining_uses,
        ]);
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $request->validate([
            'code'           => 'sometimes|string|max:30|unique:promotions,code,' . $promotion->id,
            'name'           => 'sometimes|string|max:100',
            'discount_type'  => 'sometimes|in:percent,fixed',
            'discount_value' => 'sometimes|numeric|min:1',
            'min_booking'    => 'nullable|numeric|min:0',
            'max_uses'       => 'nullable|integer|min:1',
            'valid_from'     => 'sometimes|date|date_format:Y-m-d',
            'valid_until'    => 'sometimes|date|date_format:Y-m-d|after_or_equal:valid_from',
            'is_active'      => 'sometimes|boolean',
        ]);

        if ($request->has('code')) {
            $request->merge(['code' => strtoupper($request->code)]);
        }

        $promotion->update($request->all());

        return $this->successResponse($promotion, 'Promo berhasil diperbarui.');
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        if ($promotion->bookings()->exists()) {
            return $this->errorResponse(
                'Promo tidak bisa dihapus karena sudah pernah digunakan.',
                422
            );
        }

        $promotion->delete();

        return $this->successResponse(null, 'Promo berhasil dihapus.');
    }
}
