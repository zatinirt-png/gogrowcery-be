<?php

namespace App\Http\Requests\Bounty;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBountyBidItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'      => ['required', 'in:approved,rejected'],
            'proof_photo' => [
                'required_if:status,approved',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            'catatan'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'proof_photo.required_if' => 'Foto bukti wajib dilampirkan saat approve.',
            'proof_photo.mimes'       => 'Foto harus berformat JPG atau PNG.',
            'proof_photo.max'         => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
