<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Bounty;
use App\Models\BountyBid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BountyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $supplierProfile = $request->user()->supplierProfile;

        // Ambil bounty published
        $bounties = Cache::remember('bounties.published', 300, function () {
            return Bounty::with('items')
                ->where('status', 'published')
                ->latest('published_at')
                ->get()
                ->toArray();
        });

        // Inject has_bid dan bid_status per bounty
        // Tidak bisa di-cache karena per-supplier berbeda
        if ($supplierProfile) {
            $myBids = BountyBid::where('supplier_profile_id', $supplierProfile->id)
                ->whereIn('status', ['submitted', 'revised'])
                ->get()
                ->keyBy('bounty_id');

            $bounties = collect($bounties)->map(function ($bounty) use ($myBids) {
                $bid = $myBids->get($bounty['id']);
                $bounty['has_bid']   = !is_null($bid);
                $bounty['bid_status'] = $bid?->status;
                $bounty['bid_id']    = $bid?->id;
                return $bounty;
            })->toArray();
        }

        return response()->json($bounties);
    }

    public function show(Request $request, Bounty $bounty): JsonResponse
    {
        if (!$bounty->isPublished()) {
            return response()->json([
                'message' => 'Bounty tidak tersedia.',
            ], 404);
        }

        $supplierProfile = $request->user()->supplierProfile;

        $data = Cache::remember("bounties.{$bounty->id}", 300, function () use ($bounty) {
            return $bounty->load('items')->toArray();
        });

        // Inject my_bid — tidak di-cache karena per-supplier
        $myBid = null;
        if ($supplierProfile) {
            $myBid = BountyBid::with('items.bountyItem')
                ->where('bounty_id', $bounty->id)
                ->where('supplier_profile_id', $supplierProfile->id)
                ->whereIn('status', ['submitted', 'revised'])
                ->first();
        }

        $data['has_bid']   = !is_null($myBid);
        $data['bid_status'] = $myBid?->status;
        $data['my_bid']    = $myBid;

        return response()->json(['data' => $data]);
    }
}
