<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isApproving = $this->input('action') === 'approve';

        return [
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],

            // Foto wajib hanya saat approve
            'foto_kebun' => [$isApproving ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'foto_akses_jalan' => [$isApproving ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'foto_pic' => [$isApproving ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action wajib diisi (approve/reject).',
            'action.in' => 'Action harus approve atau reject.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi saat reject.',
            'foto_kebun.required' => 'Foto kebun wajib dilampirkan saat approve.',
            'foto_akses_jalan.required' => 'Foto akses jalan wajib dilampirkan saat approve.',
            'foto_pic.required' => 'Foto PIC wajib dilampirkan saat approve.',
            'foto_kebun.mimes' => 'Foto kebun harus berformat JPG atau PNG.',
            'foto_akses_jalan.mimes' => 'Foto akses jalan harus berformat JPG atau PNG.',
            'foto_pic.mimes' => 'Foto PIC harus berformat JPG atau PNG.',
        ];
    }
}
