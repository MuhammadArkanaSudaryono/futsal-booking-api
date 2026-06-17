<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldImage;
use App\Services\FileUploadService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    use ApiResponse;

    public function __construct(private FileUploadService $fileUploadService) {}

    public function index(Request $request): JsonResponse
    {
        $fields = Field::with(['fieldType', 'images'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('bookings')
            ->orderBy('name')
            ->paginate($request->per_page ?? 10);

        return $this->paginatedResponse($fields);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'field_type_id'  => 'required|exists:field_types,id',
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string',
            'price_per_hour' => 'required|numeric|min:1000',
            'facilities'     => 'nullable|array',
            'facilities.*'   => 'string|max:50',
            'status'         => 'sometimes|in:active,inactive,maintenance',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $field = Field::create($request->only([
            'field_type_id', 'name', 'description', 'price_per_hour', 'facilities', 'status',
        ]));

        // Upload gambar jika ada
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $this->fileUploadService->upload($image, 'fields');
                FieldImage::create([
                    'field_id'   => $field->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0, // Gambar pertama jadi primary
                ]);
            }
        }

        return $this->createdResponse(
            $field->load(['fieldType', 'images']),
            'Lapangan berhasil ditambahkan.'
        );
    }

    public function show(Field $field): JsonResponse
    {
        $field->load(['fieldType', 'images', 'timeSlots']);
        $field->loadCount('bookings');

        return $this->successResponse($field);
    }

    public function update(Request $request, Field $field): JsonResponse
    {
        $request->validate([
            'field_type_id'  => 'sometimes|exists:field_types,id',
            'name'           => 'sometimes|string|max:100',
            'description'    => 'nullable|string',
            'price_per_hour' => 'sometimes|numeric|min:1000',
            'facilities'     => 'nullable|array',
            'facilities.*'   => 'string|max:50',
            'status'         => 'sometimes|in:active,inactive,maintenance',
        ]);

        $field->update($request->only([
            'field_type_id', 'name', 'description', 'price_per_hour', 'facilities', 'status',
        ]));

        return $this->successResponse(
            $field->load(['fieldType', 'images']),
            'Lapangan berhasil diperbarui.'
        );
    }

    public function destroy(Field $field): JsonResponse
    {
        // Cegah hapus jika ada booking aktif
        $hasActiveBooking = $field->bookings()
            ->whereNotIn('status', ['cancelled', 'rejected', 'completed'])
            ->exists();

        if ($hasActiveBooking) {
            return $this->errorResponse('Lapangan tidak bisa dihapus karena masih ada booking aktif.', 422);
        }

        // Hapus semua gambar dari storage
        foreach ($field->images as $image) {
            $this->fileUploadService->delete($image->image_path);
        }

        $field->delete();

        return $this->successResponse(null, 'Lapangan berhasil dihapus.');
    }

    // POST /api/admin/fields/{field}/images
    public function uploadImage(Request $request, Field $field): JsonResponse
    {
        $request->validate([
            'image'      => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'is_primary' => 'sometimes|boolean',
        ]);

        if ($request->boolean('is_primary')) {
            // Reset primary flag dulu
            $field->images()->update(['is_primary' => false]);
        }

        $path = $this->fileUploadService->upload($request->file('image'), 'fields');

        $image = FieldImage::create([
            'field_id'   => $field->id,
            'image_path' => $path,
            'is_primary' => $request->boolean('is_primary', false),
        ]);

        return $this->createdResponse([
            'id'         => $image->id,
            'url'        => $image->image_url,
            'is_primary' => $image->is_primary,
        ], 'Gambar berhasil diupload.');
    }

    // DELETE /api/admin/fields/{field}/images/{image}
    public function deleteImage(Field $field, FieldImage $image): JsonResponse
    {
        if ($image->field_id !== $field->id) {
            return $this->notFoundResponse('Gambar tidak ditemukan pada lapangan ini.');
        }

        $this->fileUploadService->delete($image->image_path);
        $image->delete();

        return $this->successResponse(null, 'Gambar berhasil dihapus.');
    }
}
