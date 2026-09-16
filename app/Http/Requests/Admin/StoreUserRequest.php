<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [
            'admin',
            'superadmin',
        ], true);
    }

    public function rules(): array
    {
        $currentUser = $this->user();

        $allowedRoles = $currentUser?->role === 'superadmin'
            ? ['sales', 'admin', 'superadmin']
            : ['sales'];

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:users,username',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'nohp' => [
                'required',
                'string',
                'max:30',
            ],

            'role' => [
                'required',
                Rule::in($allowedRoles),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
