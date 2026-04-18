<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BountyService
{
    public function create(array $data, User $admin): Bounty
    {
        return DB::transaction(function () use ($data, $admin) {
            $bounty = Bounty::create([
                'code'                 => Bounty::generateCode(),
                'client_name'          => $data['client_name'],
                'title'                => $data['title'],
                'description'          => $data['description'] ?? null,
                'deadline_at'          => $data['deadline_at'],
                'original_deadline_at' => $data['deadline_at'],
                'status'               => 'draft',
                'created_by'           => $admin->id,
            ]);

            $bounty->items()->createMany($data['items']);

            return $bounty->load('items', 'createdBy');
        });
    }

    public function update(Bounty $bounty, array $data, User $admin): Bounty
    {
        return DB::transaction(function () use ($bounty, $data, $admin) {
            $bounty->update([
                'client_name' => $data['client_name'] ?? $bounty->client_name,
                'title'       => $data['title'] ?? $bounty->title,
                'description' => $data['description'] ?? $bounty->description,
                'deadline_at' => $data['deadline_at'] ?? $bounty->deadline_at,
                'updated_by'  => $admin->id,
            ]);

            // Sync items jika dikirim
            if (isset($data['items'])) {
                $bounty->items()->delete();
                $bounty->items()->createMany($data['items']);
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

        return $bounty->fresh('items');
    }

    public function extendDeadline(Bounty $bounty, string $newDeadline, User $admin): Bounty
    {
        // Validasi: harus lebih besar dari deadline aktif
        $activeDeadline = $bounty->extended_deadline_at ?? $bounty->deadline_at;

        if (strtotime($newDeadline) <= strtotime($activeDeadline)) {
            throw new \InvalidArgumentException(
                'Deadline baru harus lebih besar dari deadline aktif saat ini (' .
                $activeDeadline->format('Y-m-d H:i') . ').'
            );
        }

        $bounty->update([
            'deadline_at'          => $newDeadline,
            'extended_deadline_at' => $newDeadline,
            'updated_by'           => $admin->id,
        ]);

        return $bounty->fresh('items');
    }
}
