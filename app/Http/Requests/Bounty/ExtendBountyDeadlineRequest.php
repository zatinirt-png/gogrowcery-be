<?php

namespace App\Http\Requests\Bounty;

use Illuminate\Foundation\Http\FormRequest;

class ExtendBountyDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_deadline' => ['required', 'date', 'after:now'],
        ];
    }
}
