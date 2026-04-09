<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['supplier_profile_id', 'payout_method', 'bank_name', 'bank_account_number', 'bank_account_name', 'bank_branch', 'ewallet_name', 'ewallet_account_number', 'ewallet_account_name', 'is_active'])]
class SupplierPayoutAccount extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function supplierProfile()
    {
        return $this->belongsTo(SupplierProfile::class);
    }
}
