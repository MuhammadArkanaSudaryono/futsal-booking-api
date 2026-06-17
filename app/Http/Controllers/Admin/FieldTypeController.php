<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldType;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldTypeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse(FieldType::withCount('fields')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:50|unique:field_types,name',
            'description' => 'nullable|string',
        ]);

        $fieldType = FieldType::create($request->only(['name', 'description']));

        return $this->createdResponse($fieldType, 'Jenis lapangan berhasil ditambahkan.');
    }

    public function show(FieldType $fieldType): JsonResponse
    {
        return $this->successResponse($fieldType->loadCount('fields'));
    }

    public function update(Request $request, FieldType $fieldType): JsonResponse
    {
        $request->validate([
            'name'        => 'sometimes|string|max:50|unique:field_types,name,' . $fieldType->id,
            'description' => 'nullable|string',
        ]);

        $fieldType->update($request->only(['name', 'description']));

        return $this->successResponse($fieldType, 'Jenis lapangan berhasil diperbarui.');
    }

    public function destroy(FieldType $fieldType): JsonResponse
    {
        if ($fieldType->fields()->exists()) {
            return $this->errorResponse(
                'Jenis lapangan tidak bisa dihapus karena masih memiliki lapangan terdaftar.',
                422
            );
        }

        $fieldType->delete();

        return $this->successResponse(null, 'Jenis lapangan berhasil dihapus.');
    }
}
