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
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'store_name' => ['required', 'string', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'npwp'       => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string'],
        ];
    }
}
