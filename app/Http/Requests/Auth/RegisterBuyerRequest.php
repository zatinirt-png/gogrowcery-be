<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterBuyerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
     public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:100', 'unique:users,username'],
            'email'     => ['nullable', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'phone'     => ['nullable', 'string', 'max:20'],
        ];
    }
}
