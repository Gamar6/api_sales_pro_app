<?php

namespace App\Http\Requests;

use App\Models\VisitReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitVisitCorrectionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key')
                ?? $this->input('idempotency_key'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pic_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'activities' => ['sometimes', 'nullable', 'array', 'min:1'],
            'activities.*' => ['string', 'max:255'],
            'stock_percentage' => ['sometimes', 'nullable', 'integer', 'between:0,100'],
            'stock_pcs' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'string', 'max:128'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasCorrectableField = collect(VisitReport::CORRECTABLE_FIELDS)
                ->contains(fn (string $field): bool => $this->exists($field));

            if (! $hasCorrectableField) {
                $validator->errors()->add(
                    'correction_data',
                    'Setidaknya satu field laporan harus dikoreksi.'
                );
            }
        });
    }
}
