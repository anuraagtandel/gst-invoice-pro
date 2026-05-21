<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchemeRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'valid_from' => 'required|date|after_or_equal:today',
            'valid_to' => 'required|date|after_or_equal:valid_from',
            'slabs' => 'required|array|min:1',
            'slabs.*.min_qty' => 'required|integer|min:1',
            'slabs.*.max_qty' => 'nullable|integer|min:1|gte:slabs.*.min_qty',
            'slabs.*.free_qty' => 'required|integer|min:1',
            'slabs.*.free_product_id' => 'required|exists:products,id',
        ];
    }
}
