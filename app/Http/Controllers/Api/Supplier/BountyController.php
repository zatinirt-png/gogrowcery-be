<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Bounty;
use Illuminate\Http\JsonResponse;

class BountyController extends Controller
{
    // Supplier hanya bisa lihat bounty yang published
    public function index(): JsonResponse
    {
        $bounties = Bounty::with('items')
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(15);

        return response()->json($bounties);
    }

    public function show(Bounty $bounty): JsonResponse
    {
        if (!$bounty->isPublished()) {
            return response()->json([
                'message' => 'Bounty tidak tersedia.',
            ], 404);
        }

        $bounty->load('items');

        return response()->json(['data' => $bounty]);
    }
}
