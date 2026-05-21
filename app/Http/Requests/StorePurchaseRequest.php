<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bill_no' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_ct' => 'required|numeric|min:0',
            'items.*.purchase_rate' => 'required|numeric|min:0|decimal:0,6',
        ];
    }
}
