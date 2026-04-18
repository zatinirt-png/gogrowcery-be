<?php

namespace App\Http\Requests\Bounty;

use Illuminate\Foundation\Http\FormRequest;

class StoreBountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline_at' => ['required', 'date', 'after:now'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.target_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal 1 item harus diisi.',
            'items.min' => 'Minimal 1 item harus diisi.',
            'deadline_at.after' => 'Deadline harus di masa depan.',
        ];
    }
}
