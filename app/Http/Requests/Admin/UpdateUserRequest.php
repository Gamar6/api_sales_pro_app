<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        $targetUser = $this->route('user');

        if (!$currentUser || !$targetUser instanceof User) {
            return false;
        }

        if (!in_array($currentUser->role, [
            'admin',
            'superadmin',
        ], true)) {
            return false;
        }

        if ($currentUser->role === 'superadmin') {
            return true;
        }

        return $targetUser->role === 'sales';
    }

    public function rules(): array
    {
        $user = $this->route('user');

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
                Rule::unique('users', 'username')
                    ->ignore($user->id),
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'nohp' => [
                'required',
                'string',
                'max:30',
            ],

            'role' => [
                'required',
                Rule::in(['sales', 'admin', 'superadmin']),
            ],
        ];
    }
}
