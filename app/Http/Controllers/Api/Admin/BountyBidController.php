<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bounty;
use App\Models\BountyBid;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class BountyBidController extends Controller
{
    // List semua bid untuk satu bounty
    public function index(Bounty $bounty): JsonResponse
    {
        $bids = Cache::remember(
            "bounty.{$bounty->id}.bids",
            300,
            fn() => BountyBid::with(['items.bountyItem', 'supplierProfile.user'])
                ->where('bounty_id', $bounty->id)
                ->whereNotIn('status', ['withdrawn'])
                ->latest()
                ->get()
                ->toArray(),
        );

        return response()->json([
            'message' => 'Daftar bid untuk bounty ini.',
            'data' => $bids,
        ]);
    }

    // Detail satu bid
    public function show(Bounty $bounty, BountyBid $bountyBid): JsonResponse
    {
        // Pastikan bid milik bounty ini
        if ($bountyBid->bounty_id !== $bounty->id) {
            return response()->json(
                [
                    'message' => 'Bid tidak ditemukan.',
                ],
                404,
            );
        }

        $bountyBid->load(['items.bountyItem', 'supplierProfile.user', 'supplierProfile.lands']);

        return response()->json([
            'message' => 'Detail bid.',
            'data' => $bountyBid,
        ]);
    }
}
