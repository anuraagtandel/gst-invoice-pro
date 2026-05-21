<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $upper = function ($value) {
            $v = trim((string) $value);
            return $v === '' ? '' : strtoupper($v);
        };

        $round6 = function ($value) {
            if ($value === null || $value === '') {
                return $value;
            }
            if (!is_numeric($value)) {
                return $value;
            }
            return round((float) $value, 6);
        };

        $data = [
            'product_code' => $upper($this->input('product_code')),
            'name' => $upper($this->input('name')),
            'brand' => $upper($this->input('brand')),
            'volume' => $upper($this->input('volume')),
            'pack_type' => $upper($this->input('pack_type')),
            'category' => $upper($this->input('category')),
            'base_price' => $round6($this->input('base_price')),
            'trade_price' => $round6($this->input('trade_price')),
        ];

        if ($this->has('purchase_rate')) {
            $data['purchase_rate'] = $round6($this->input('purchase_rate'));
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'product_code' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'volume' => 'required|string|max:100',
            'unit_type' => 'nullable|string|in:CT',
            'pack_type' => 'nullable|string|max:50',
            'pack_size' => 'required|integer|min:1|exists:pack_sizes,units_per_ct',
            'mrp' => 'required|numeric|min:0',
            'purchase_rate' => 'nullable|numeric|min:0|decimal:0,6',
            'base_price' => 'required|numeric|min:0|decimal:0,6',
            'trade_price' => 'nullable|numeric|min:0|decimal:0,6',
            'gst_rate' => 'required|numeric|in:0,5,6,9,12,18,28,40',
            'hsn_code' => 'required|digits:8',
            'category' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required.',
            'name.max' => 'The product name cannot exceed 100 characters.',
            'pack_size.required' => 'Pack size is required.',
            'pack_size.min' => 'Pack size must be at least 1.',
            'mrp.required' => 'MRP is required.',
            'mrp.numeric' => 'MRP must be a valid number.',
            'mrp.min' => 'MRP cannot be negative.',
            'gst_rate.required' => 'GST rate is required.',
            'gst_rate.in' => 'Invalid GST rate selected.',
            'hsn_code.required' => 'HSN code is required.',
            'hsn_code.digits' => 'HSN code must be exactly 8 digits.',
        ];
    }
}
