<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyBidItemApproval extends Model
{
    protected $fillable = [
        'bounty_bid_item_id',
        'approved_by',
        'status',
        'proof_photo_path',
        'catatan',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function bidItem()
    {
        return $this->belongsTo(BountyBidItem::class, 'bounty_bid_item_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
