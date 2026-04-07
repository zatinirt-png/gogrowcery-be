<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterBuyerRequest;
use App\Http\Requests\Auth\RegisterSupplierRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register Buyer
    public function registerBuyer(RegisterBuyerRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => $request->password,
                'role'      => 'buyer',
                'is_active' => true,
            ]);

            $user->buyerProfile()->create([
                'full_name' => $request->full_name,
                'phone'     => $request->phone,
            ]);

            $token = $user->createToken('buyer-token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil.',
                'token'   => $token,
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Register Supplier (self-register, status: pending)
    public function registerSupplier(RegisterSupplierRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => $request->password,
                'role'      => 'supplier',
                'is_active' => false, // belum aktif sampai di-approve
            ]);

            $user->supplierProfile()->create([
                'store_name'          => $request->store_name,
                'phone'               => $request->phone,
                'npwp'                => $request->npwp,
                'address'             => $request->address,
                'approval_status'     => 'pending',
                'registered_by_admin' => false,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Registrasi supplier berhasil. '
                           . 'Akun kamu sedang menunggu persetujuan admin.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registrasi gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    // Login (semua role)
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user = Auth::user();

        // Cek apakah akun aktif
        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'message' => 'Akun kamu belum aktif atau sedang menunggu persetujuan admin.',
            ], 403);
        }

        // Cek khusus supplier: harus sudah approved
        if ($user->isSupplier() && !$user->isApprovedSupplier()) {
            Auth::logout();
            return response()->json([
                'message' => 'Akun supplier kamu belum disetujui admin.',
            ], 403);
        }

        // Hapus token lama, buat yang baru
        $user->tokens()->delete();
        $token = $user->createToken("{$user->role}-token")->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
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
        $user = $request->user()->load(
            $request->user()->isSupplier() ? 'supplierProfile' : 'buyerProfile'
        );

        return response()->json(['user' => $user]);
    }
}
