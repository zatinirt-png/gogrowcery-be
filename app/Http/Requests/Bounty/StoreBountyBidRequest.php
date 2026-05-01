<?php

namespace App\Http\Requests\Bounty;

use Illuminate\Foundation\Http\FormRequest;

class StoreBountyBidRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes'                          => ['nullable', 'string', 'max:1000'],

            // Items — wajib minimal 1
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.bounty_item_id'         => ['required', 'integer', 'exists:bounty_items,id'],
            'items.*.grade'                  => ['required', 'in:A,B,C'],
            'items.*.estimasi_harga'         => ['required', 'numeric', 'min:1'],
            'items.*.estimasi_kuantitas'     => ['required', 'numeric', 'min:0.01'],
            'items.*.catatan'                => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                     => 'Minimal 1 item harus di-bid.',
            'items.*.bounty_item_id.exists'      => 'Item bounty tidak valid.',
            'items.*.grade.in'                   => 'Grade harus A, B, atau C.',
            'items.*.estimasi_harga.min'         => 'Estimasi harga harus lebih dari 0.',
            'items.*.estimasi_kuantitas.min'     => 'Estimasi kuantitas harus lebih dari 0.',
        ];
    }
}
