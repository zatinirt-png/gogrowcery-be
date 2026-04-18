<?php

namespace App\Http\Requests\Bounty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBountyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:draft,published,closed,cancelled'],
        ];
    }
}
