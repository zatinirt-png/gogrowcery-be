<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierProfilePhoto extends Model
{
    protected $fillable = [
        'supplier_profile_id',
        'foto_kebun_path',
        'foto_akses_jalan_path',
        'foto_pic_path',
        'uploaded_by',
    ];

    public function supplierProfile()
    {
        return $this->belongsTo(SupplierProfile::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
