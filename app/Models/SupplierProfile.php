<?php

namespace App\Models;

use App\Models\SupplierLand;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'nama_lengkap', 'no_ktp', 'ktp_document_path', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pendidikan', 'status_perkawinan', 'no_hp', 'email', 'alamat_domisili', 'desa', 'kecamatan', 'kabupaten', 'kontak_darurat', 'bahasa_komunikasi', 'npwp_document_path', 'approval_status', 'survey_status', 'approved_by', 'approved_at', 'rejection_reason', 'survey_notes', 'registered_by_admin'])]
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

    // Accessor — return download URL lewat Laravel, bukan expose R2 langsung
    public function getKtpDocumentUrlAttribute(): ?string
    {
        return $this->ktp_document_path
            ? route('admin.suppliers.documents', [
                'supplierProfile' => $this->id,
                'type' => 'ktp',
            ])
            : null;
    }

    public function getNpwpDocumentUrlAttribute(): ?string
    {
        return $this->npwp_document_path
            ? route('admin.suppliers.documents', [
                'supplierProfile' => $this->id,
                'type' => 'npwp',
            ])
            : null;
    }
}
