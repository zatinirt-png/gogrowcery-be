<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyBidItem extends Model
{
    protected $fillable = [
        'bounty_bid_id',
        'bounty_item_id',
        'grade',
        'estimasi_harga',
        'estimasi_kuantitas',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'estimasi_harga'     => 'decimal:2',
            'estimasi_kuantitas' => 'decimal:2',
        ];
    }

    public function bid()
    {
        return $this->belongsTo(BountyBid::class, 'bounty_bid_id');
    }

    public function bountyItem()
    {
        return $this->belongsTo(BountyItem::class);
    }
}
