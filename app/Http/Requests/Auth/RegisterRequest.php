<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['required', 'string', 'regex:/^0[789][01]\d{8}$/', 'unique:users,phone'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'address'    => ['nullable', 'string', 'max:255'],
            'state'      => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please provide a valid Nigerian phone number (e.g. 08012345678)',
        ];
    }
}
