<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pan = $this->input('pan');
        if (is_string($pan)) {
            $this->merge(['pan' => strtoupper(trim($pan))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'gstin' => 'nullable|string|max:50',
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'state_code' => 'nullable|string|max:10',
            'pos_code' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ];
    }
}
