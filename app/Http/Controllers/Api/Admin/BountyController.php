<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bounty\ExtendBountyDeadlineRequest;
use App\Http\Requests\Bounty\StoreBountyRequest;
use App\Http\Requests\Bounty\UpdateBountyRequest;
use App\Http\Requests\Bounty\UpdateBountyStatusRequest;
use App\Models\Bounty;
use App\Services\BountyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BountyController extends Controller
{
    public function __construct(private BountyService $bountyService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'in:draft,published,closed,cancelled'],
            'has_bids' => ['nullable', 'in:0,1,true,false'],
        ]);

        $cacheKey = 'bounties.admin.all.' . md5(json_encode($request->only([
            'status', 'has_bids', 'page'
        ])));

        $bounties = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Bounty::with(['items', 'createdBy'])
                ->withCount([
                    'bids as total_bids' => fn($q) => $q->whereIn('status', ['submitted', 'revised'])
                ]);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter bounty yang sudah ada bid atau belum
            if ($request->filled('has_bids')) {
                $hasBids = filter_var($request->has_bids, FILTER_VALIDATE_BOOLEAN);
                if ($hasBids) {
                    $query->has('bids');
                } else {
                    $query->doesntHave('bids');
                }
            }

            return $query->latest()->paginate(15)->toArray();
        });

        return response()->json($bounties);
    }

    public function store(StoreBountyRequest $request): JsonResponse
    {
        $bounty = $this->bountyService->create(
            $request->validated(),
            $request->user()
        );

        Cache::forget('bounties.admin.all');

        return response()->json([
            'message' => 'Bounty berhasil dibuat.',
            'data'    => $bounty,
        ], 201);
    }

    public function show(Bounty $bounty): JsonResponse
    {
        $data = Cache::remember("bounties.admin.{$bounty->id}", 300, function () use ($bounty) {
            return $bounty->load(['items', 'createdBy', 'updatedBy'])->toArray();
        });

        // Bidding progress — tidak di-cache karena berubah sering
        $bounty->loadMissing('items', 'bids.items');
        $data['bidding_progress'] = $this->bountyService->getBiddingProgress($bounty);

        return response()->json(['data' => $data]);
    }

    public function update(UpdateBountyRequest $request, Bounty $bounty): JsonResponse
    {
        $bounty = $this->bountyService->update(
            $bounty,
            $request->validated(),
            $request->user()
        );

        Cache::forget('bounties.admin.all');
        Cache::forget("bounties.admin.{$bounty->id}");

        return response()->json([
            'message' => 'Bounty berhasil diupdate.',
            'data'    => $bounty,
        ]);
    }

    public function updateStatus(UpdateBountyStatusRequest $request, Bounty $bounty): JsonResponse
    {
        $bounty = $this->bountyService->updateStatus(
            $bounty,
            $request->status,
            $request->user()
        );

        Cache::forget('bounties.admin.all');
        Cache::forget("bounties.admin.{$bounty->id}");

        return response()->json([
            'message' => 'Status bounty berhasil diupdate.',
            'data'    => $bounty,
        ]);
    }

    public function extendDeadline(ExtendBountyDeadlineRequest $request, Bounty $bounty): JsonResponse
    {
        try {
            $bounty = $this->bountyService->extendDeadline(
                $bounty,
                $request->new_deadline,
                $request->user()
            );

            Cache::forget('bounties.admin.all');
            Cache::forget("bounties.admin.{$bounty->id}");

            return response()->json([
                'message' => 'Deadline bounty berhasil diperpanjang.',
                'data'    => $bounty,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
