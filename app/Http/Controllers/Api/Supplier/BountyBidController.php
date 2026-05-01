<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bounty\StoreBountyBidRequest;
use App\Models\Bounty;
use App\Models\BountyBid;
use App\Services\BountyBidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BountyBidController extends Controller
{
    public function __construct(private BountyBidService $bidService) {}

    // Submit atau revisi bid
    public function submitOrRevise(StoreBountyBidRequest $request, Bounty $bounty): JsonResponse
    {
        $supplierProfile = $request->user()->supplierProfile;

        if (!$supplierProfile) {
            return response()->json(
                [
                    'message' => 'Supplier profile tidak ditemukan.',
                ],
                403,
            );
        }

        if (!$supplierProfile->isApproved()) {
            return response()->json(
                [
                    'message' => 'Akun supplier belum disetujui admin.',
                ],
                403,
            );
        }

        try {
            $bid = $this->bidService->submitOrRevise($bounty, $supplierProfile, $request->validated());

            $isRevision = $bid->status === 'revised';

            return response()->json(
                [
                    'message' => $isRevision ? 'Bid berhasil direvisi.' : 'Bid berhasil disubmit.',
                    'data' => $bid,
                ],
                $isRevision ? 200 : 201,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                [
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    // Lihat bid sendiri untuk bounty tertentu
    public function myBid(Request $request, Bounty $bounty): JsonResponse
    {
        $supplierProfile = $request->user()->supplierProfile;

        $bid = BountyBid::with('items.bountyItem')
            ->where('bounty_id', $bounty->id)
            ->where('supplier_profile_id', $supplierProfile->id)
            ->whereNotIn('status', ['withdrawn'])
            ->first();

        if (!$bid) {
            return response()->json([
                'message' => 'Kamu belum mengajukan bid untuk bounty ini.',
                'data' => null,
            ]);
        }

        return response()->json([
            'message' => 'Bid ditemukan.',
            'data' => $bid,
        ]);
    }

    // Withdraw bid
    public function withdraw(Request $request, Bounty $bounty): JsonResponse
    {
        $supplierProfile = $request->user()->supplierProfile;

        try {
            $bid = $this->bidService->withdraw($bounty, $supplierProfile);

            return response()->json([
                'message' => 'Bid berhasil di-withdraw.',
                'data' => $bid,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(
                [
                    'message' => 'Bid tidak ditemukan.',
                ],
                404,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                [
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    // List semua bid aktif milik supplier
    public function myBids(Request $request): JsonResponse
    {
        $supplierProfile = $request->user()->supplierProfile;

        if (!$supplierProfile) {
            return response()->json(
                [
                    'message' => 'Supplier profile tidak ditemukan.',
                ],
                403,
            );
        }

        $bids = BountyBid::with(['bounty.items', 'items.bountyItem'])
            ->where('supplier_profile_id', $supplierProfile->id)
            ->whereIn('status', ['submitted', 'revised'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Daftar bid aktif kamu.',
            'data' => $bids,
        ]);
    }
}
