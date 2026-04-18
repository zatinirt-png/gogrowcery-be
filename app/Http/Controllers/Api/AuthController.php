<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterBuyerRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register Buyer
    public function registerBuyer(RegisterBuyerRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'buyer',
                'is_active' => true,
            ]);

            $user->buyerProfile()->create([
                'full_name' => $request->full_name,
                'phone' => $request->phone,
            ]);

            $token = $user->createToken('buyer-token')->plainTextToken;

            DB::commit();

            return response()->json(
                [
                    'message' => 'Registrasi berhasil.',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // Login (semua role)
    public function login(LoginRequest $request): JsonResponse
    {
        // Cari user berdasarkan username
        $user = User::where('username', $request->username)->first();

        // Username tidak ditemukan atau password salah
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        // Cek apakah akun aktif
        if (!$user->is_active) {
            return response()->json(
                [
                    'message' => 'Akun kamu belum aktif atau sedang menunggu persetujuan admin.',
                ],
                403,
            );
        }

        // Cek khusus supplier: harus sudah approved
        if ($user->isSupplier() && !$user->isApprovedSupplier()) {
            return response()->json(
                [
                    'message' => 'Akun supplier kamu belum disetujui admin.',
                ],
                403,
            );
        }

        // Hapus token lama, buat yang baru
        $user->tokens()->delete();
        $token = $user->createToken("{$user->role}-token")->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    // Logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    // Get current user
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load(
            match ($user->user_type) {
                'supplier' => 'supplierProfile',
                'buyer' => 'buyerProfile',
                default => [],
            },
        );

        return response()->json(['user' => $user]);
    }
}
