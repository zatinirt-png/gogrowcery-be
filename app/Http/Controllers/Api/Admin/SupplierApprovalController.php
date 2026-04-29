<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplierProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SupplierApprovalController extends Controller
{
    // Daftar supplier pending
    public function index(): JsonResponse
    {
        $suppliers = Cache::remember('suppliers.pending', 300, function () {
            return SupplierProfile::with(['user', 'lands', 'payoutAccount'])
                ->where('approval_status', 'pending')
                ->latest()
                ->get()
                ->toArray();
        });

        return response()->json([
            'message' => 'Daftar supplier pending.',
            'data'    => $suppliers,
        ]);
    }

    // Semua supplier — bisa filter by status, search, registered_by_admin
    public function all(Request $request): JsonResponse
    {
        $request->validate([
            'status'              => ['nullable', 'in:pending,approved,rejected'],
            'registered_by_admin' => ['nullable', 'in:0,1,true,false'],
            'search'              => ['nullable', 'string', 'max:100'],
        ]);

        // Cache key unik per kombinasi filter
        $cacheKey = 'suppliers.all.' . md5(json_encode($request->only([
            'status', 'registered_by_admin', 'search', 'page'
        ])));

        $suppliers = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = SupplierProfile::with([
                'user',
                'lands',
                'payoutAccount',
                'approvedBy',
            ]);

            // Filter by approval_status
            if ($request->filled('status')) {
                $query->where('approval_status', $request->status);
            }

            // Filter by registered_by_admin
            if ($request->filled('registered_by_admin')) {
                $query->where(
                    'registered_by_admin',
                    filter_var($request->registered_by_admin, FILTER_VALIDATE_BOOLEAN)
                );
            }

            // Search by nama, no_hp, no_ktp
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
            'data'    => $suppliers,
        ]);
    }

    // Detail satu supplier
    public function show(SupplierProfile $supplierProfile): JsonResponse
    {
        $data = Cache::remember("suppliers.{$supplierProfile->id}", 300, function () use ($supplierProfile) {
            return $supplierProfile->load([
                'user',
                'lands',
                'payoutAccount',
                'approvedBy',
            ])->toArray();
        });

        return response()->json([
            'message' => 'Detail supplier.',
            'data'    => $data,
        ]);
    }

    // Approve supplier
    public function approve(Request $request, SupplierProfile $supplierProfile): JsonResponse
    {
        if ($supplierProfile->isApproved()) {
            return response()->json([
                'message' => 'Supplier sudah dalam status approved.',
            ], 422);
        }

        $supplierProfile->update([
            'approval_status'  => 'approved',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ]);

        $supplierProfile->user->update([
            'is_active' => true,
        ]);

        $this->invalidateCache($supplierProfile->id);

        return response()->json([
            'message' => 'Supplier berhasil diapprove.',
            'data'    => [
                'supplier_id'     => $supplierProfile->id,
                'nama_lengkap'    => $supplierProfile->nama_lengkap,
                'approval_status' => $supplierProfile->approval_status,
                'approved_by'     => $request->user()->name,
                'approved_at'     => $supplierProfile->approved_at,
            ],
        ]);
    }

    // Reject supplier
    public function reject(Request $request, SupplierProfile $supplierProfile): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($supplierProfile->isRejected()) {
            return response()->json([
                'message' => 'Supplier sudah dalam status rejected.',
            ], 422);
        }

        $supplierProfile->update([
            'approval_status'  => 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $supplierProfile->user->update([
            'is_active' => false,
        ]);

        $this->invalidateCache($supplierProfile->id);

        return response()->json([
            'message' => 'Supplier berhasil direject.',
            'data'    => [
                'supplier_id'      => $supplierProfile->id,
                'nama_lengkap'     => $supplierProfile->nama_lengkap,
                'approval_status'  => $supplierProfile->approval_status,
                'rejection_reason' => $supplierProfile->rejection_reason,
            ],
        ]);
    }

    // Download dokumen KTP/NPWP
    public function downloadDocument(
        SupplierProfile $supplierProfile,
        string $type
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        $path = match($type) {
            'ktp'  => $supplierProfile->ktp_document_path,
            'npwp' => $supplierProfile->npwp_document_path,
            default => null,
        };

        if (!$path || !Storage::disk('r2')->exists($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        return Storage::disk('r2')->download($path);
    }

    // Helper invalidate cache
    private function invalidateCache(int $supplierProfileId): void
    {
        Cache::forget('suppliers.pending');
        Cache::forget("suppliers.{$supplierProfileId}");

        // Flush semua cache suppliers.all.* karena ada perubahan status
        // Redis tidak support wildcard delete native,
        // jadi kita pakai tag atau biarkan expire sendiri (TTL 5 menit)
        // Untuk MVP ini cukup — kalau butuh instant invalidate pakai Redis tags nanti
    }
}
