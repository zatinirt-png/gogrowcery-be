<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\BountyBid;
use App\Models\SupplierProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BountyBidService
{
    public function submitOrRevise(Bounty $bounty, SupplierProfile $supplierProfile, array $data): BountyBid
    {
        // Validasi bounty masih published
        if (!$bounty->isPublished()) {
            throw new \InvalidArgumentException('Bounty tidak tersedia untuk bidding.');
        }

        // Validasi deadline belum lewat
        $activeDeadline = $bounty->extended_deadline_at ?? $bounty->deadline_at;
        if (now()->isAfter($activeDeadline)) {
            throw new \InvalidArgumentException('Deadline bounty sudah lewat.');
        }

        // Validasi semua bounty_item_id milik bounty ini
        $validItemIds = $bounty->items->pluck('id')->toArray();
        foreach ($data['items'] as $item) {
            if (!in_array($item['bounty_item_id'], $validItemIds)) {
                throw new \InvalidArgumentException("Item ID {$item['bounty_item_id']} bukan bagian dari bounty ini.");
            }
        }

        return DB::transaction(function () use ($bounty, $supplierProfile, $data) {
            // Cek semua bid — termasuk withdrawn
            $existingBid = BountyBid::where('bounty_id', $bounty->id)->where('supplier_profile_id', $supplierProfile->id)->first();

            if ($existingBid) {
                // Apapun statusnya (submitted, revised, withdrawn) — update saja
                $existingBid->update([
                    'status' => $existingBid->isWithdrawn() ? 'submitted' : 'revised',
                    'notes' => $data['notes'] ?? $existingBid->notes,
                    'submitted_at' => $existingBid->isWithdrawn() ? now() : $existingBid->submitted_at,
                    'revised_at' => $existingBid->isWithdrawn() ? null : now(),
                    'withdrawn_at' => null, // reset withdrawn
                ]);

                // Sync items
                $existingBid->items()->delete();
                $existingBid->items()->createMany($data['items']);

                Cache::forget("bounty.{$bounty->id}.bids");

                return $existingBid->load('items.bountyItem');
            }

            // Buat bid baru (pertama kali)
            $bid = BountyBid::create([
                'bounty_id' => $bounty->id,
                'supplier_profile_id' => $supplierProfile->id,
                'status' => 'submitted',
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            $bid->items()->createMany($data['items']);

            Cache::forget("bounty.{$bounty->id}.bids");

            return $bid->load('items.bountyItem');
        });
    }

    public function withdraw(Bounty $bounty, SupplierProfile $supplierProfile): BountyBid
    {
        $bid = BountyBid::where('bounty_id', $bounty->id)
            ->where('supplier_profile_id', $supplierProfile->id)
            ->whereNotIn('status', ['withdrawn'])
            ->firstOrFail();

        if ($bounty->isClosed() || $bounty->isCancelled()) {
            throw new \InvalidArgumentException('Tidak bisa withdraw bid — bounty sudah closed atau cancelled.');
        }

        $bid->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        Cache::forget("bounty.{$bounty->id}.bids");

        return $bid->fresh();
    }
}
