<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['supplier_profile_id', 'nama_pemilik', 'no_hp', 'alamat_lahan', 'desa', 'kelurahan', 'kecamatan', 'kabupaten', 'provinsi', 'latitude', 'longitude', 'akses_kendaraan', 'catatan_akses', 'kepemilikan', 'kepemilikan_lainnya_keterangan', 'luas_lahan_m2', 'status_aktif'])]
class SupplierLand extends Model
{
    public function supplierProfile()
    {
        return $this->belongsTo(SupplierProfile::class);
    }
}
