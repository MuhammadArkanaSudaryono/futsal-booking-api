<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PromotionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use ApiResponse;

    public function __construct(private PromotionService $promotionService) {}

    // POST /api/promotions/validate
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $result = $this->promotionService->validateCode(
            $request->code,
            (float) $request->amount
        );

        if (! $result['valid']) {
            return $this->errorResponse($result['message'], 422);
        }

        $promo = $result['promotion'];

        return $this->successResponse([
            'promotion_id'   => $promo->id,
            'code'           => $promo->code,
            'name'           => $promo->name,
            'discount_type'  => $promo->discount_type,
            'discount_value' => (float) $promo->discount_value,
            'discount_amount'=> $result['discount'],
            'final_amount'   => max(0, (float) $request->amount - $result['discount']),
        ], $result['message']);
    }
}
