<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Services\AvailabilityService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    use ApiResponse;

    public function __construct(private AvailabilityService $availabilityService) {}

    // GET /api/fields
    public function index(Request $request): JsonResponse
    {
        $query = Field::with(['fieldType', 'images'])
            ->active();

        // Filter opsional
        if ($request->field_type_id) {
            $query->where('field_type_id', $request->field_type_id);
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $fields = $query->orderBy('name')->paginate($request->per_page ?? 10);

        $fields->setCollection(
            collect($fields->items())->map(
                fn ($f) => $this->formatField($f)
            )
        );

        return $this->paginatedResponse($fields);
    }

    // GET /api/fields/{field}
    public function show(Field $field): JsonResponse
    {
        if (! $field->isActive()) {
            return $this->notFoundResponse('Lapangan tidak tersedia.');
        }

        $field->load(['fieldType', 'images']);

        return $this->successResponse($this->formatField($field, detail: true));
    }

    // GET /api/fields/{field}/availability?date=2024-06-15
    public function availability(Request $request, Field $field): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
        ], [
            'date.required'          => 'Tanggal wajib diisi.',
            'date.date_format'       => 'Format tanggal harus Y-m-d (contoh: 2024-06-15).',
            'date.after_or_equal'    => 'Tanggal tidak boleh di masa lalu.',
        ]);

        if (! $field->isActive()) {
            return $this->notFoundResponse('Lapangan tidak tersedia.');
        }

        $slots = $this->availabilityService->getSlots($field, $request->date);

        return $this->successResponse([
            'field_id'        => $field->id,
            'field_name'      => $field->name,
            'date'            => $request->date,
            'price_per_hour'  => (float) $field->price_per_hour,
            'slots'           => $slots,
        ]);
    }

    // ── Helper ─────────────────────────────────────────────────

    private function formatField(Field $field, bool $detail = false): array
    {
        $base = [
            'id'              => $field->id,
            'name'            => $field->name,
            'field_type'      => $field->fieldType?->name,
            'price_per_hour'  => (float) $field->price_per_hour,
            'status'          => $field->status,
            'primary_image'   => $field->primary_image_url,
            'images'          => $field->images->map(fn ($img) => [
                'id'         => $img->id,
                'url'        => $img->image_url,
                'is_primary' => $img->is_primary,
            ]),
        ];

        if ($detail) {
            $base['description'] = $field->description;
            $base['facilities']  = $field->facilities ?? [];
        }

        return $base;
    }
}
