<?php

namespace App\Models;

use App\Models\SupplierLand;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'nama_lengkap', 'no_ktp', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pendidikan', 'status_perkawinan', 'no_hp', 'alamat_domisili', 'desa', 'kecamatan', 'kabupaten', 'kontak_darurat', 'bahasa_komunikasi', 'approval_status', 'survey_status', 'approved_by', 'approved_at', 'rejection_reason', 'survey_notes', 'registered_by_admin'])]
class SupplierProfile extends Model
{
    protected function casts(): array
    {
        return [
            'bahasa_komunikasi' => 'array',
            'tanggal_lahir' => 'date',
            'approved_at' => 'datetime',
            'registered_by_admin' => 'boolean',
        ];
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lands()
    {
        return $this->hasMany(SupplierLand::class);
    }

    public function payoutAccount()
    {
        return $this->hasOne(SupplierPayoutAccount::class);
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }
}
