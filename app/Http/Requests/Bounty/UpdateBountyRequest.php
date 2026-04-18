<?php

namespace App\Http\Requests\Bounty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['sometimes', 'required', 'string', 'max:255'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline_at' => ['sometimes', 'required', 'date', 'after:now'],

            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.target_quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required_with:items', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
