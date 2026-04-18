<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Bounty;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class BountyController extends Controller
{
    public function index(): JsonResponse
    {
        $bounties = Cache::remember('bounties.published', 300, function () {
            return Bounty::with('items')
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(15)
                ->toArray(); // ← simpan sebagai array
        });

        return response()->json($bounties);
    }

    public function show(Bounty $bounty): JsonResponse
    {
        if (!$bounty->isPublished()) {
            return response()->json([
                'message' => 'Bounty tidak tersedia.',
            ], 404);
        }

        $data = Cache::remember("bounties.{$bounty->id}", 300, function () use ($bounty) {
            return $bounty->load('items')->toArray(); // ← simpan sebagai array
        });

        return response()->json(['data' => $data]);
    }
}
