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
use Illuminate\Support\Facades\Cache;

class BountyController extends Controller
{
    public function __construct(private BountyService $bountyService) {}

    public function index(): JsonResponse
    {
        $bounties = Cache::remember('bounties.admin.all', 300, function () {
            return Bounty::with(['items', 'createdBy'])
                ->latest()
                ->paginate(15)
                ->toArray();
        });

        return response()->json($bounties);
    }

    public function store(StoreBountyRequest $request): JsonResponse
    {
        $bounty = $this->bountyService->create(
            $request->validated(),
            $request->user()
        );

        // Invalidate admin list cache
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

        return response()->json(['data' => $data]);
    }

    public function update(UpdateBountyRequest $request, Bounty $bounty): JsonResponse
    {
        $bounty = $this->bountyService->update(
            $bounty,
            $request->validated(),
            $request->user()
        );

        // Invalidate cache
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

        // Invalidate semua cache terkait
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

            // Invalidate cache
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
