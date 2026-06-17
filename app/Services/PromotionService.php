<?php

namespace App\Services;

use App\Models\Promotion;

class PromotionService
{
    /**
     * Cari dan validasi kode promo untuk total booking tertentu.
     *
     * @return array ['valid' => bool, 'message' => string, 'promotion' => Promotion|null, 'discount' => float]
     */
    public function validateCode(string $code, float $bookingTotal): array
    {
        $promotion = Promotion::where('code', strtoupper($code))->first();

        if (! $promotion) {
            return [
                'valid'     => false,
                'message'   => 'Kode promo tidak ditemukan.',
                'promotion' => null,
                'discount'  => 0,
            ];
        }

        $validation = $promotion->validate($bookingTotal);

        if (! $validation['valid']) {
            return [
                'valid'     => false,
                'message'   => $validation['message'],
                'promotion' => null,
                'discount'  => 0,
            ];
        }

        $discount = $promotion->calculateDiscount($bookingTotal);

        return [
            'valid'     => true,
            'message'   => 'Promo berhasil diterapkan.',
            'promotion' => $promotion,
            'discount'  => $discount,
        ];
    }
}
