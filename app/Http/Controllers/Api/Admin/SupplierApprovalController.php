<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplierProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupplierApprovalController extends Controller
{
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

    public function show(SupplierProfile $supplierProfile): JsonResponse
    {
        $data = Cache::remember("suppliers.{$supplierProfile->id}", 300, function () use ($supplierProfile) {
            return $supplierProfile->load(['user', 'lands', 'payoutAccount'])->toArray();
        });

        return response()->json([
            'message' => 'Detail supplier.',
            'data'    => $data,
        ]);
    }

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

        // Invalidate cache
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

        // Invalidate cache
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

    private function invalidateCache(int $supplierProfileId): void
    {
        Cache::forget('suppliers.pending');
        Cache::forget("suppliers.{$supplierProfileId}");
    }
}
