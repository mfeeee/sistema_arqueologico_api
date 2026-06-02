<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['sometimes', 'string', 'max:255'],
            'email'                 => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'password'              => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required_with:password'],
        ];
    }
}
