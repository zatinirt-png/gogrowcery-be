<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveSupplierRequest;
use App\Models\SupplierProfile;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupplierApprovalController extends Controller
{
    public function __construct(private DocumentUploadService $documentUploadService) {}

    // List supplier pending
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'registered_by_admin' => ['nullable', 'in:0,1,true,false'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $cacheKey = 'suppliers.all.' . md5(json_encode($request->only(['status', 'registered_by_admin', 'search', 'page'])));

        $suppliers = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = SupplierProfile::with(['user']) // ← ringan, hanya user
                ->withCount('lands'); // ← jumlah lahan saja

            if ($request->filled('status')) {
                $query->where('approval_status', $request->status);
            }

            if ($request->filled('registered_by_admin')) {
                $query->where('registered_by_admin', filter_var($request->registered_by_admin, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ilike', "%{$search}%")
                        ->orWhere('no_hp', 'ilike', "%{$search}%")
                        ->orWhere('no_ktp', 'ilike', "%{$search}%");
                });
            }

            return $query->latest()->paginate(15)->toArray();
        });

        return response()->json([
            'message' => 'Daftar supplier.',
            'data' => $suppliers,
        ]);
    }

    // List semua supplier dengan filter
    public function all(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'registered_by_admin' => ['nullable', 'in:0,1,true,false'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $cacheKey = 'suppliers.all.' . md5(json_encode($request->only(['status', 'registered_by_admin', 'search', 'page'])));

        $suppliers = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = SupplierProfile::with(['user', 'lands', 'payoutAccount', 'approvedBy', 'photos']);

            if ($request->filled('status')) {
                $query->where('approval_status', $request->status);
            }

            if ($request->filled('registered_by_admin')) {
                $query->where('registered_by_admin', filter_var($request->registered_by_admin, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ilike', "%{$search}%")
                        ->orWhere('no_hp', 'ilike', "%{$search}%")
                        ->orWhere('no_ktp', 'ilike', "%{$search}%");
                });
            }

            return $query->latest()->paginate(15)->toArray();
        });

        return response()->json([
            'message' => 'Daftar supplier.',
            'data' => $suppliers,
        ]);
    }

    // Detail supplier
    public function show(SupplierProfile $supplierProfile): JsonResponse
    {
        $data = Cache::remember("suppliers.{$supplierProfile->id}", 300, function () use ($supplierProfile) {
            return $supplierProfile->load(['user', 'lands', 'payoutAccount', 'approvedBy', 'photos.uploadedBy'])->toArray();
        });

        return response()->json([
            'message' => 'Detail supplier.',
            'data' => $data,
        ]);
    }

    // Approve atau Reject — satu endpoint
    public function approveOrReject(ApproveSupplierRequest $request, SupplierProfile $supplierProfile): JsonResponse
    {
        // Cek status sudah final
        if ($supplierProfile->isApproved()) {
            return response()->json(
                [
                    'message' => 'Supplier sudah dalam status approved.',
                ],
                422,
            );
        }

        if ($supplierProfile->isRejected()) {
            return response()->json(
                [
                    'message' => 'Supplier sudah dalam status rejected.',
                ],
                422,
            );
        }

        $action = $request->input('action');

        DB::beginTransaction();

        try {
            $fotoPaths = [];

            if ($action === 'approve') {
                // Upload ketiga foto
                $fotoPaths['kebun'] = $this->documentUploadService->uploadSupplierPhoto($request->file('foto_kebun'), $supplierProfile->id, 'kebun');
                $fotoPaths['akses_jalan'] = $this->documentUploadService->uploadSupplierPhoto($request->file('foto_akses_jalan'), $supplierProfile->id, 'akses_jalan');
                $fotoPaths['pic'] = $this->documentUploadService->uploadSupplierPhoto($request->file('foto_pic'), $supplierProfile->id, 'pic');

                // Simpan foto ke tabel
                $supplierProfile->photos()->updateOrCreate(
                    ['supplier_profile_id' => $supplierProfile->id],
                    [
                        'foto_kebun_path' => $fotoPaths['kebun'],
                        'foto_akses_jalan_path' => $fotoPaths['akses_jalan'],
                        'foto_pic_path' => $fotoPaths['pic'],
                        'uploaded_by' => $request->user()->id,
                    ],
                );

                // Update supplier profile
                $supplierProfile->update([
                    'approval_status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'survey_notes' => $request->catatan,
                    'rejection_reason' => null,
                ]);

                // Aktifkan user
                $supplierProfile->user->update(['is_active' => true]);
            } else {
                // Reject — tanpa foto
                $supplierProfile->update([
                    'approval_status' => 'rejected',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'rejection_reason' => $request->rejection_reason,
                    'survey_notes' => $request->catatan,
                ]);

                $supplierProfile->user->update(['is_active' => false]);
            }

            DB::commit();

            $this->invalidateCache($supplierProfile->id);

            return response()->json([
                'message' => $action === 'approve' ? 'Supplier berhasil diapprove.' : 'Supplier berhasil direject.',
                'data' => [
                    'supplier_id' => $supplierProfile->id,
                    'nama_lengkap' => $supplierProfile->nama_lengkap,
                    'approval_status' => $supplierProfile->approval_status,
                    'actioned_by' => $request->user()->name,
                    'approved_at' => $supplierProfile->approved_at,
                    'rejection_reason' => $supplierProfile->rejection_reason,
                    'has_photos' => $action === 'approve',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Hapus foto yang sudah terupload
            foreach ($fotoPaths as $path) {
                $this->documentUploadService->delete($path);
            }

            return response()->json(
                [
                    'message' => 'Gagal memproses approval.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ],
                500,
            );
        }
    }

    // Download foto survey
    public function downloadPhoto(SupplierProfile $supplierProfile, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $photos = $supplierProfile->photos;

        if (!$photos) {
            abort(404, 'Foto tidak ditemukan.');
        }

        $path = match ($type) {
            'kebun' => $photos->foto_kebun_path,
            'akses_jalan' => $photos->foto_akses_jalan_path,
            'pic' => $photos->foto_pic_path,
            default => null,
        };

        if (!$path || !Storage::disk('r2')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('r2')->download($path);
    }

    // Download dokumen KTP/NPWP
    public function downloadDocument(SupplierProfile $supplierProfile, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = match ($type) {
            'ktp' => $supplierProfile->ktp_document_path,
            'npwp' => $supplierProfile->npwp_document_path,
            default => null,
        };

        if (!$path || !Storage::disk('r2')->exists($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        return Storage::disk('r2')->download($path);
    }

    private function invalidateCache(int $supplierProfileId): void
    {
        Cache::forget('suppliers.pending');
        Cache::forget("suppliers.{$supplierProfileId}");
    }
}
