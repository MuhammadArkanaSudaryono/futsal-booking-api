<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use ApiResponse;

    /**
     * Validasi kode promo sebelum booking
     * POST /api/promotions/validate
     */
    public function validatePromo(Request $request): JsonResponse
    {
        $request->validate([
            'code'     => 'required|string|max:30',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code = strtoupper($request->code);
        $subtotal = (float) $request->subtotal;

        // Cari promo yang aktif, belum expired, dan masih punya kuota
        $promo = Promotion::where('code', $code)
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', now())
            ->whereDate('valid_until', '>=', now())
            ->first();

        if (!$promo) {
            return $this->errorResponse('Kode promo tidak ditemukan atau sudah kadaluarsa.', 404);
        }

        // Cek kuota penggunaan
        if ($promo->max_uses !== null && $promo->used_count >= $promo->max_uses) {
            return $this->errorResponse('Kode promo sudah mencapai batas maksimal penggunaan.', 422);
        }

        // Cek minimal booking
        if ($promo->min_booking !== null && $subtotal < $promo->min_booking) {
            return $this->errorResponse(
                sprintf('Minimal booking Rp %s untuk menggunakan promo ini.', number_format($promo->min_booking, 0, ',', '.')),
                422
            );
        }

        // Hitung diskon
        $discountAmount = 0;
        if ($promo->discount_type === 'percent') {
            $discountAmount = $subtotal * ($promo->discount_value / 100);
        } else {
            $discountAmount = $promo->discount_value;
        }

        // Pastikan diskon tidak melebihi subtotal
        $discountAmount = min($discountAmount, $subtotal);

        return $this->successResponse([
            'id'              => $promo->id,
            'code'            => $promo->code,
            'name'            => $promo->name,
            'discount_type'   => $promo->discount_type,
            'discount_value'  => $promo->discount_value,
            'discount_amount' => $discountAmount,
            'subtotal_after'  => $subtotal - $discountAmount,
        ], 'Kode promo valid.');
    }
}
