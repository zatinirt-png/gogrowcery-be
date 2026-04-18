<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyItem extends Model
{
    protected $fillable = [
        'bounty_id',
        'item_name',
        'target_quantity',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_quantity' => 'decimal:2',
        ];
    }

    public function bounty()
    {
        return $this->belongsTo(Bounty::class);
    }
}
