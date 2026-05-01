<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BountyService
{
    public function create(array $data, User $admin): Bounty
    {
        return DB::transaction(function () use ($data, $admin) {
            $bounty = Bounty::create([
                'code' => Bounty::generateCode(),
                'client_name' => $data['client_name'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'deadline_at' => $data['deadline_at'],
                'original_deadline_at' => $data['deadline_at'],
                'status' => 'draft',
                'created_by' => $admin->id,
            ]);

            $bounty->items()->createMany($data['items']);

            // Cache belum perlu di-invalidate saat create
            // karena status masih draft, belum masuk published list

            return $bounty->load('items', 'createdBy');
        });
    }

    public function update(Bounty $bounty, array $data, User $admin): Bounty
    {
        return DB::transaction(function () use ($bounty, $data, $admin) {
            $bounty->update([
                'client_name' => $data['client_name'] ?? $bounty->client_name,
                'title' => $data['title'] ?? $bounty->title,
                'description' => $data['description'] ?? $bounty->description,
                'deadline_at' => $data['deadline_at'] ?? $bounty->deadline_at,
                'updated_by' => $admin->id,
            ]);

            if (isset($data['items'])) {
                $bounty->items()->delete();
                $bounty->items()->createMany($data['items']);
            }

            // Invalidate cache kalau bounty ini published
            if ($bounty->isPublished()) {
                $this->invalidateCache($bounty->id);
            }

            return $bounty->load('items', 'createdBy', 'updatedBy');
        });
    }

    public function updateStatus(Bounty $bounty, string $status, User $admin): Bounty
    {
        $extra = ['updated_by' => $admin->id];

        if ($status === 'published' && !$bounty->published_at) {
            $extra['published_at'] = now();
        }

        if ($status === 'cancelled') {
            $extra['cancelled_at'] = now();
        }

        $bounty->update(array_merge(['status' => $status], $extra));

        // Invalidate cache — status berubah selalu affect published list
        $this->invalidateCache($bounty->id);

        return $bounty->fresh('items');
    }

    public function extendDeadline(Bounty $bounty, string $newDeadline, User $admin): Bounty
    {
        $activeDeadline = $bounty->extended_deadline_at ?? $bounty->deadline_at;

        if (strtotime($newDeadline) <= strtotime($activeDeadline)) {
            throw new \InvalidArgumentException('Deadline baru harus lebih besar dari deadline aktif saat ini (' . $activeDeadline->format('Y-m-d H:i') . ').');
        }

        $bounty->update([
            'deadline_at' => $newDeadline,
            'extended_deadline_at' => $newDeadline,
            'updated_by' => $admin->id,
        ]);

        // Invalidate cache kalau bounty ini published
        if ($bounty->isPublished()) {
            $this->invalidateCache($bounty->id);
        }

        return $bounty->fresh('items');
    }

    public function getBiddingProgress(Bounty $bounty): array
    {
        $totalItems = $bounty->items->count();
        $totalTargetQty = $bounty->items->sum('target_quantity');

        $bids = $bounty
            ->bids()
            ->with('items')
            ->whereIn('status', ['submitted', 'revised'])
            ->get();

        $totalBidders = $bids->count();

        // Per item — berapa supplier yang bid + total estimasi kuantitas
        $itemProgress = $bounty->items->map(function ($item) use ($bids) {
            $bidItemsForThisItem = $bids->flatMap->items->where('bounty_item_id', $item->id);

            return [
                'bounty_item_id' => $item->id,
                'item_name' => $item->item_name,
                'target_quantity' => $item->target_quantity,
                'unit' => $item->unit,
                'total_bidders' => $bidItemsForThisItem->count(),
                'total_estimasi_qty' => $bidItemsForThisItem->sum('estimasi_kuantitas'),
                'fulfillment_pct' => $item->target_quantity > 0 ? round(($bidItemsForThisItem->sum('estimasi_kuantitas') / $item->target_quantity) * 100, 1) : 0,
                'grades_available' => $bidItemsForThisItem->pluck('grade')->unique()->values(),
                'price_range' => [
                    'min' => $bidItemsForThisItem->min('estimasi_harga'),
                    'max' => $bidItemsForThisItem->max('estimasi_harga'),
                    'avg' => $bidItemsForThisItem->count() > 0 ? round($bidItemsForThisItem->avg('estimasi_harga'), 0) : null,
                ],
            ];
        });

        return [
            'total_items' => $totalItems,
            'total_bidders' => $totalBidders,
            'item_progress' => $itemProgress,
        ];
    }

    // Helper — invalidate semua cache terkait bounty
    private function invalidateCache(int $bountyId): void
    {
        Cache::forget('bounties.published');
        Cache::forget("bounties.{$bountyId}");
    }
}
