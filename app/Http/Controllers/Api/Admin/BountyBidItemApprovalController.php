<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bounty\ApproveBountyBidItemRequest;
use App\Models\Bounty;
use App\Models\BountyBid;
use App\Models\BountyBidItem;
use App\Models\BountyBidItemApproval;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BountyBidItemApprovalController extends Controller
{
    public function __construct(private DocumentUploadService $documentUploadService) {}

    // Approve atau reject satu item dalam bid
    public function approve(ApproveBountyBidItemRequest $request, Bounty $bounty, BountyBid $bountyBid, BountyBidItem $bountyBidItem): JsonResponse
    {
        // Pastikan bid milik bounty ini
        if ($bountyBid->bounty_id !== $bounty->id) {
            return response()->json(
                [
                    'message' => 'Bid tidak ditemukan dalam bounty ini.',
                ],
                404,
            );
        }

        // Pastikan item milik bid ini
        if ($bountyBidItem->bounty_bid_id !== $bountyBid->id) {
            return response()->json(
                [
                    'message' => 'Item tidak ditemukan dalam bid ini.',
                ],
                404,
            );
        }

        // Cek apakah sudah pernah di-approve/reject
        $existingApproval = $bountyBidItem->approval;
        if ($existingApproval) {
            return response()->json(
                [
                    'message' => 'Item ini sudah pernah di-' . $existingApproval->status . '.',
                    'data' => $existingApproval,
                ],
                422,
            );
        }

        DB::beginTransaction();

        try {
            $photoPath = null;

            // Upload foto bukti jika ada
            if ($request->hasFile('proof_photo')) {
                $photoPath = $this->documentUploadService->uploadProofPhoto($request->file('proof_photo'), $bountyBidItem->id);
            }

            $approval = BountyBidItemApproval::create([
                'bounty_bid_item_id' => $bountyBidItem->id,
                'approved_by' => $request->user()->id,
                'status' => $request->status,
                'proof_photo_path' => $photoPath,
                'catatan' => $request->catatan,
                'approved_at' => now(),
            ]);

            DB::commit();

            // Invalidate cache
            Cache::forget("bounties.admin.{$bounty->id}");
            Cache::forget("bounty.{$bounty->id}.bids");

            return response()->json(
                [
                    'message' => 'Item bid berhasil di-' . $request->status . '.',
                    'data' => $approval->load('approvedBy'),
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();

            if ($photoPath) {
                $this->documentUploadService->delete($photoPath);
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

    // Lihat detail approval satu item
    public function show(Bounty $bounty, BountyBid $bountyBid, BountyBidItem $bountyBidItem): JsonResponse
    {
        if ($bountyBid->bounty_id !== $bounty->id) {
            return response()->json(['message' => 'Bid tidak ditemukan.'], 404);
        }

        if ($bountyBidItem->bounty_bid_id !== $bountyBid->id) {
            return response()->json(['message' => 'Item tidak ditemukan.'], 404);
        }

        $approval = $bountyBidItem->load(['approval.approvedBy', 'bountyItem']);

        return response()->json([
            'message' => 'Detail item bid.',
            'data' => $approval,
        ]);
    }

    // Download foto bukti
    public function downloadProof(Bounty $bounty, BountyBid $bountyBid, BountyBidItem $bountyBidItem): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $approval = $bountyBidItem->approval;

        if (!$approval || !$approval->proof_photo_path) {
            abort(404, 'Foto bukti tidak ditemukan.');
        }

        if (!Storage::disk('r2')->exists($approval->proof_photo_path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        return Storage::disk('r2')->download($approval->proof_photo_path);
    }
}
