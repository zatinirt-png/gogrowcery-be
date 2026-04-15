<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSupplierRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierCreateController extends Controller
{
    public function store(RegisterSupplierRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Generate username dari nama jika tidak diisi
            $username = $request->username
                ?? Str::slug($request->nama_lengkap, '_') . '_' . Str::random(4);

            // Pastikan username unik
            while (User::where('username', $username)->exists()) {
                $username = Str::slug($request->nama_lengkap, '_') . '_' . Str::random(4);
            }

            // Generate password sementara
            $temporaryPassword = Str::random(10);

            // 1. Buat user account
            $user = User::create([
                'name'      => $request->name,
                'username'  => $username,
                'email'     => $request->email,
                'password'  => $temporaryPassword,
                'user_type' => 'supplier',
                'is_active' => false, // tetap pending sampai di-approve
            ]);

            // 2. Buat supplier profile
            $profile = $user->supplierProfile()->create([
                'nama_lengkap'        => $request->nama_lengkap,
                'no_ktp'              => $request->no_ktp,
                'tempat_lahir'        => $request->tempat_lahir,
                'tanggal_lahir'       => $request->tanggal_lahir,
                'jenis_kelamin'       => $request->jenis_kelamin,
                'pendidikan'          => $request->pendidikan,
                'status_perkawinan'   => $request->status_perkawinan,
                'no_hp'               => $request->no_hp,
                'email'               => $request->email,
                'alamat_domisili'     => $request->alamat_domisili,
                'desa'                => $request->desa,
                'kecamatan'           => $request->kecamatan,
                'kabupaten'           => $request->kabupaten,
                'kontak_darurat'      => $request->kontak_darurat,
                'bahasa_komunikasi'   => $request->bahasa_komunikasi ?? [],
                'approval_status'     => 'pending',
                'survey_status'       => 'belum_survey',
                'registered_by_admin' => true, // ← bedanya di sini
            ]);

            // 3. Buat lands
            $landsData = collect($request->lands)->map(fn($land) => [
                'nama_lahan'                     => $land['nama_lahan'],
                'nama_pemilik'                   => $land['nama_pemilik'],
                'no_hp'                          => $land['no_hp'],
                'alamat_lahan'                   => $land['alamat_lahan'],
                'desa'                           => $land['desa'],
                'kelurahan'                      => $land['kelurahan'] ?? null,
                'kecamatan'                      => $land['kecamatan'],
                'kabupaten'                      => $land['kabupaten'],
                'provinsi'                       => $land['provinsi'],
                'latitude'                       => $land['latitude'] ?? null,
                'longitude'                      => $land['longitude'] ?? null,
                'akses_kendaraan'                => $land['akses_kendaraan'] ?? null,
                'catatan_akses'                  => $land['catatan_akses'] ?? null,
                'kepemilikan'                    => $land['kepemilikan'],
                'kepemilikan_lainnya_keterangan' => $land['kepemilikan_lainnya_keterangan'] ?? null,
                'luas_lahan_m2'                  => $land['luas_lahan_m2'],
                'status_aktif'                   => $land['status_aktif'],
            ])->toArray();

            $profile->lands()->createMany($landsData);

            // 4. Buat payout account
            $payout = $request->payout;
            $profile->payoutAccount()->create([
                'payout_method'          => $payout['payout_method'],
                'bank_name'              => $payout['bank_name'] ?? null,
                'bank_account_number'    => $payout['bank_account_number'] ?? null,
                'bank_account_name'      => $payout['bank_account_name'] ?? null,
                'bank_branch'            => $payout['bank_branch'] ?? null,
                'ewallet_name'           => $payout['ewallet_name'] ?? null,
                'ewallet_account_number' => $payout['ewallet_account_number'] ?? null,
                'ewallet_account_name'   => $payout['ewallet_account_name'] ?? null,
                'is_active'              => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Supplier berhasil didaftarkan oleh admin.',
                'data'    => [
                    'username'          => $user->username,
                    'temporary_password'=> $temporaryPassword, // tampilkan sekali saja
                    'nama_lengkap'      => $profile->nama_lengkap,
                    'approval_status'   => $profile->approval_status,
                    'jumlah_lahan'      => count($request->lands),
                    'registered_by_admin' => true,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Pendaftaran supplier gagal.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
