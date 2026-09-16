<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        $targetUser = $this->route('user');

        if (!$currentUser || !$targetUser instanceof User) {
            return false;
        }

        if ($currentUser->role === 'superadmin') {
            return true;
        }

        return $currentUser->role === 'admin'
            && $targetUser->role === 'sales';
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
