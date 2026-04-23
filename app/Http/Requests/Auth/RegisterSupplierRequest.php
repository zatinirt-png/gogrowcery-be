<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Account — username & password opsional kalau dari admin
            'name' => ['required', 'string', 'max:255'],
            'username' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:users,username'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],

            // Supplier Profile
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'no_ktp' => ['required', 'string', 'size:16', 'unique:supplier_profiles,no_ktp'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:laki_laki,perempuan'],
            'pendidikan' => ['nullable', 'string', 'max:100'],
            'status_perkawinan' => ['required', 'in:belum_kawin,kawin,janda_duda'],
            'no_hp' => ['required', 'string', 'max:20', 'unique:supplier_profiles,no_hp'],
            'alamat_domisili' => ['required', 'string'],
            'desa' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kabupaten' => ['required', 'string', 'max:100'],
            'kontak_darurat' => ['nullable', 'string', 'max:255'],
            'bahasa_komunikasi' => ['nullable', 'array'],
            'bahasa_komunikasi.*' => ['string'],
            'ktp_document'  => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
'npwp_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],

            // Lands — minimal 1
            'lands' => ['required', 'array', 'min:1'],
            'lands.*.nama_lahan' => ['required', 'string', 'max:255'],
            'lands.*.nama_pemilik' => ['required', 'string', 'max:255'],
            'lands.*.no_hp' => ['required', 'string', 'max:20'],
            'lands.*.alamat_lahan' => ['required', 'string'],
            'lands.*.desa' => ['required', 'string', 'max:100'],
            'lands.*.kelurahan' => ['nullable', 'string', 'max:100'],
            'lands.*.kecamatan' => ['required', 'string', 'max:100'],
            'lands.*.kabupaten' => ['required', 'string', 'max:100'],
            'lands.*.provinsi' => ['required', 'string', 'max:100'],
            'lands.*.latitude' => ['nullable', 'numeric'],
            'lands.*.longitude' => ['nullable', 'numeric'],
            'lands.*.akses_kendaraan' => ['nullable', 'string', 'max:100'],
            'lands.*.catatan_akses' => ['nullable', 'string'],
            'lands.*.kepemilikan' => ['required', 'in:milik_sendiri,sewa,kerjasama,lainnya'],
            'lands.*.kepemilikan_lainnya_keterangan' => ['nullable', 'required_if:lands.*.kepemilikan,lainnya', 'string'],
            'lands.*.luas_lahan_m2' => ['required', 'numeric', 'min:1'],
            'lands.*.status_aktif' => ['required', 'in:aktif,tidak_aktif,musiman'],

            // Payout
            'payout' => ['required', 'array'],
            'payout.payout_method' => ['required', 'in:transfer,ewallet'],
            'payout.bank_name' => ['nullable', 'required_if:payout.payout_method,transfer', 'string', 'max:100'],
            'payout.bank_account_number' => ['nullable', 'required_if:payout.payout_method,transfer', 'string', 'max:50'],
            'payout.bank_account_name' => ['nullable', 'required_if:payout.payout_method,transfer', 'string', 'max:255'],
            'payout.bank_branch' => ['nullable', 'string', 'max:100'],
            'payout.ewallet_name' => ['nullable', 'required_if:payout.payout_method,ewallet', 'string', 'max:100'],
            'payout.ewallet_account_number' => ['nullable', 'required_if:payout.payout_method,ewallet', 'string', 'max:50'],
            'payout.ewallet_account_name' => ['nullable', 'required_if:payout.payout_method,ewallet', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'lands.required' => 'Minimal 1 lahan harus diisi.',
            'lands.min' => 'Minimal 1 lahan harus diisi.',
            'no_ktp.size' => 'Nomor KTP harus 16 digit.',
            'no_ktp.unique' => 'Nomor KTP sudah terdaftar.',
            'no_hp.unique' => 'Nomor HP sudah terdaftar.',
            'username.unique' => 'Username sudah digunakan.',
            'payout.payout_method.required' => 'Metode payout wajib dipilih.',
        ];
    }
}
