<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVisitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pic_name'         => 'required|string|max:255',
            'activities'       => 'required|array|min:1',
            'activities.*'     => 'string|max:255',
            'stock_percentage' => 'nullable|integer|min:0|max:100|required_without:stock_pcs',
            'stock_pcs'        => 'nullable|integer|min:0|required_without:stock_percentage',
            'notes'            => 'nullable|string',
            'photos'           => 'required|array|min:1|max:4',
            'photos.*'         => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'pic_name.required'             => 'Nama PIC penanggung jawab wajib diisi.',
            'activities.required'           => 'Pilih minimal satu aktivitas kunjungan.',
            'stock_percentage.required_without' => 'Isi sisa stok dalam persen atau pcs.',
            'stock_pcs.required_without'    => 'Isi sisa stok dalam pcs atau persen.',
            'photos.required'               => 'Wajib melampirkan foto dokumentasi.',
            'photos.min'                    => 'Minimal lampirkan 1 foto dokumentasi.',
            'photos.max'                    => 'Maksimal lampirkan 4 foto dokumentasi.',
            'photos.*.image'                => 'File dokumentasi harus berupa gambar.',
            'photos.*.max'                  => 'Ukuran foto maksimal adalah 5MB per file.',
        ];
    }
}