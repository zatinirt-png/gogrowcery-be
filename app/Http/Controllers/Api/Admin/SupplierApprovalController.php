<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplierProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierApprovalController extends Controller
{
    // Daftar supplier pending
    public function index(): JsonResponse
    {
        $suppliers = SupplierProfile::with(['user', 'lands', 'payoutAccount'])
            ->where('approval_status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar supplier pending.',
            'data'    => $suppliers,
        ]);
    }

    // Detail satu supplier
    public function show(SupplierProfile $supplierProfile): JsonResponse
    {
        $supplierProfile->load(['user', 'lands', 'payoutAccount']);

        return response()->json([
            'message' => 'Detail supplier.',
            'data'    => $supplierProfile,
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
            'approval_status' => 'approved',
            'approved_by'     => $request->user()->id,
            'approved_at'     => now(),
            'rejection_reason' => null,
        ]);

        // Aktifkan akun user-nya
        $supplierProfile->user->update([
            'is_active' => true,
        ]);

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

        // Pastikan akun tetap tidak aktif
        $supplierProfile->user->update([
            'is_active' => false,
        ]);

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
}
