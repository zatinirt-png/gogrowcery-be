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

class BountyController extends Controller
{
    public function __construct(private BountyService $bountyService) {}

    public function index(): JsonResponse
    {
        $bounties = Bounty::with(['items', 'createdBy'])
            ->latest()
            ->paginate(15);

        return response()->json($bounties);
    }

    public function store(StoreBountyRequest $request): JsonResponse
    {
        $bounty = $this->bountyService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Bounty berhasil dibuat.',
            'data'    => $bounty,
        ], 201);
    }

    public function show(Bounty $bounty): JsonResponse
    {
        $bounty->load(['items', 'createdBy', 'updatedBy']);

        return response()->json(['data' => $bounty]);
    }

    public function update(UpdateBountyRequest $request, Bounty $bounty): JsonResponse
    {
        $bounty = $this->bountyService->update(
            $bounty,
            $request->validated(),
            $request->user()
        );

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
