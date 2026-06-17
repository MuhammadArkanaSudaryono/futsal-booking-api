<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private FileUploadService $fileUploadService) {}

    // GET /api/profile
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role'       => $user->role,
            'created_at' => $user->created_at,
        ]);
    }

    // PUT /api/profile
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'         => 'sometimes|string|max:100',
            'phone'        => 'sometimes|nullable|string|max:20',
            'password'     => 'sometimes|string|min:8|confirmed',
        ]);

        $updateData = $request->only(['name', 'phone']);

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return $this->successResponse([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ], 'Profil berhasil diperbarui.');
    }

    // POST /api/profile/avatar
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'avatar.required' => 'File avatar wajib dipilih.',
            'avatar.image'    => 'File harus berupa gambar.',
            'avatar.mimes'    => 'Format gambar harus jpg atau png.',
            'avatar.max'      => 'Ukuran gambar maksimal 2MB.',
        ]);

        $user = $request->user();

        $path = $this->fileUploadService->replace(
            $request->file('avatar'),
            $user->avatar,
            'avatars'
        );

        $user->update(['avatar' => $path]);

        return $this->successResponse([
            'avatar_url' => $user->avatar_url,
        ], 'Foto profil berhasil diperbarui.');
    }
}
