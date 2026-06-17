<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    // ── Register ───────────────────────────────────────────────

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password, // di-hash otomatis via cast
            'phone'    => $request->phone,
            'role'     => 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->createdResponse(
            $this->tokenWithUser($token, $user),
            'Registrasi berhasil.'
        );
    }

    // ── Login ──────────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $credentials = $request->only('email', 'password');
        $token       = JWTAuth::attempt($credentials);

        if (! $token) {
            return $this->errorResponse('Email atau password salah.', 401);
        }

        $user = auth()->user();

        if (! $user->is_active) {
            return $this->errorResponse('Akun Anda telah dinonaktifkan. Hubungi admin.', 403);
        }

        return $this->successResponse(
            $this->tokenWithUser($token, $user),
            'Login berhasil.'
        );
    }

    // ── Logout ─────────────────────────────────────────────────

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return $this->successResponse(null, 'Logout berhasil.');
    }

    // ── Refresh Token ──────────────────────────────────────────

    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $user     = JWTAuth::setToken($newToken)->toUser();

            return $this->successResponse(
                $this->tokenWithUser($newToken, $user),
                'Token berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui token.', 401);
        }
    }

    // ── Me (profil dari token) ─────────────────────────────────

    public function me(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at,
        ]);
    }

    // ── Helper ─────────────────────────────────────────────────

    private function tokenWithUser(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60, // detik
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar_url' => $user->avatar_url,
                'role'       => $user->role,
            ],
        ];
    }
}
