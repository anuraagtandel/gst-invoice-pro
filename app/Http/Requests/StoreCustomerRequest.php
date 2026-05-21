<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $upperNullable = function ($value) {
            if ($value === null) {
                return null;
            }
            $v = trim((string) $value);
            return $v === '' ? null : strtoupper($v);
        };
        $lowerNullable = function ($value) {
            if ($value === null) {
                return null;
            }
            $v = trim((string) $value);
            return $v === '' ? null : strtolower($v);
        };

        $this->merge([
            'name' => $upperNullable($this->input('name')),
            'code' => $upperNullable($this->input('code')),
            'gstin' => $upperNullable($this->input('gstin')),
            'pan' => $upperNullable($this->input('pan')),
            'district' => $upperNullable($this->input('district')),
            'country' => $upperNullable($this->input('country')),
            'email' => $lowerNullable($this->input('email')),
            'state_code' => $upperNullable($this->input('state_code')),
            'pos_code' => $upperNullable($this->input('pos_code')),
            'pin_code' => $upperNullable($this->input('pin_code')),
            'area_name' => $upperNullable($this->input('area_name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|unique:customers,code',
            'gstin' => 'nullable|string|max:20',
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'mobile' => ['required', 'string', 'regex:/^\d{10}$/'],
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'area_id' => 'nullable|exists:areas,id',
            'area_name' => 'nullable|string|max:100',
            'state' => 'required|string',
            'state_code' => 'nullable|string|max:10',
            'pos_code' => 'nullable|string',
            'pin_code' => ['nullable', 'string', 'regex:/^\d{6}$/'],
            'country' => 'nullable|string|max:100',
            'fssai_no' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0|decimal:0,6',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The customer name is required.',
            'code.unique' => 'This customer code is already in use.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.regex' => 'Mobile number must be exactly 10 digits.',
            'state.required' => 'State is required for GST calculations.',
        ];
    }
}
