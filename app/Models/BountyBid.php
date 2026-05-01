<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyBid extends Model
{
    protected $fillable = ['bounty_id', 'supplier_profile_id', 'status', 'notes', 'submitted_at', 'revised_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'revised_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function bounty()
    {
        return $this->belongsTo(Bounty::class);
    }

    public function supplierProfile()
    {
        return $this->belongsTo(SupplierProfile::class);
    }

    public function items()
    {
        return $this->hasMany(BountyBidItem::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
    public function isRevised(): bool
    {
        return $this->status === 'revised';
    }
    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }
}
