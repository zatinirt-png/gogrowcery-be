<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSupplierRequest;
use App\Models\User;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SupplierRegistrationController extends Controller
{
    public function __construct(private DocumentUploadService $documentUploadService) {}

    public function register(RegisterSupplierRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. Buat user account
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'supplier',
                'is_active' => false,
            ]);

            // 2. Upload dokumen ke R2
            $ktpPath = $this->documentUploadService->uploadKtp($request->file('ktp_document'), $user->username);

            $npwpPath = $request->hasFile('npwp_document') ? $this->documentUploadService->uploadNpwp($request->file('npwp_document'), $user->username) : null;

            // 3. Buat supplier profile
            $profile = $user->supplierProfile()->create([
                'nama_lengkap' => $request->nama_lengkap,
                'no_ktp' => $request->no_ktp,
                'ktp_document_path' => $ktpPath,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'jenis_kelamin' => $request->jenis_kelamin,
                'pendidikan' => $request->pendidikan,
                'status_perkawinan' => $request->status_perkawinan,
                'no_hp' => $request->no_hp,
                'email' => $request->email,
                'alamat_domisili' => $request->alamat_domisili,
                'desa' => $request->desa,
                'kecamatan' => $request->kecamatan,
                'kabupaten' => $request->kabupaten,
                'kontak_darurat' => $request->kontak_darurat,
                'bahasa_komunikasi' => $request->bahasa_komunikasi ?? [],
                'npwp_document_path' => $npwpPath,
                'approval_status' => 'pending',
                'survey_status' => 'belum_survey',
                'registered_by_admin' => false,
            ]);

            // 4. Buat lands
            $landsData = collect($request->lands)
                ->map(
                    fn($land) => [
                        'nama_lahan' => $land['nama_lahan'],
                        'nama_pemilik' => $land['nama_pemilik'],
                        'no_hp' => $land['no_hp'],
                        'alamat_lahan' => $land['alamat_lahan'],
                        'desa' => $land['desa'],
                        'kelurahan' => $land['kelurahan'] ?? null,
                        'kecamatan' => $land['kecamatan'],
                        'kabupaten' => $land['kabupaten'],
                        'provinsi' => $land['provinsi'],
                        'latitude' => $land['latitude'] ?? null,
                        'longitude' => $land['longitude'] ?? null,
                        'akses_kendaraan' => $land['akses_kendaraan'] ?? null,
                        'catatan_akses' => $land['catatan_akses'] ?? null,
                        'kepemilikan' => $land['kepemilikan'],
                        'kepemilikan_lainnya_keterangan' => $land['kepemilikan_lainnya_keterangan'] ?? null,
                        'luas_lahan_m2' => $land['luas_lahan_m2'],
                        'status_aktif' => $land['status_aktif'],
                    ],
                )
                ->toArray();

            $profile->lands()->createMany($landsData);

            // 5. Buat payout account
            $payout = $request->payout;
            $profile->payoutAccount()->create([
                'payout_method' => $payout['payout_method'],
                'bank_name' => $payout['bank_name'] ?? null,
                'bank_account_number' => $payout['bank_account_number'] ?? null,
                'bank_account_name' => $payout['bank_account_name'] ?? null,
                'bank_branch' => $payout['bank_branch'] ?? null,
                'ewallet_name' => $payout['ewallet_name'] ?? null,
                'ewallet_account_number' => $payout['ewallet_account_number'] ?? null,
                'ewallet_account_name' => $payout['ewallet_account_name'] ?? null,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Registrasi supplier berhasil. ' . 'Akun kamu sedang menunggu persetujuan admin.',
                    'data' => [
                        'username' => $user->username,
                        'nama_lengkap' => $profile->nama_lengkap,
                        'approval_status' => $profile->approval_status,
                        'jumlah_lahan' => count($request->lands),
                        'has_ktp' => true,
                        'has_npwp' => !is_null($npwpPath),
                    ],
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();

            // Hapus file yang sudah terupload kalau transaksi DB gagal
            if (isset($ktpPath)) {
                $this->documentUploadService->delete($ktpPath);
            }
            if (isset($npwpPath)) {
                $this->documentUploadService->delete($npwpPath);
            }

            return response()->json(
                [
                    'message' => 'Registrasi gagal. Silakan coba lagi.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ],
                500,
            );
        }
    }
}