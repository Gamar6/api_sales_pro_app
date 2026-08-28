<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'odoo_partner_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'odoo_partner_id.required' => 'ID Toko Odoo wajib diisi.',
            'odoo_partner_id.integer'  => 'ID Toko Odoo harus berupa angka.',
        ];
    }
}