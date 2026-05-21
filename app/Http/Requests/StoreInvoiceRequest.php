<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('payment_type')) {
            $type = (string) $this->input('payment_type');
            $this->merge([
                'payment_mode' => $type === 'Credit' ? 'Credit' : ($this->input('payment_mode') ?: 'Cash'),
            ]);
        }

        $items = $this->input('items', []);
        if (!is_array($items)) {
            return;
        }

        $filtered = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = trim((string) ($item['product_id'] ?? ''));
            $qtyCt = (float) ($item['qty_ct'] ?? 0);
            $qtyUn = (float) ($item['qty_un'] ?? 0);
            $isFree = filter_var($item['is_free'] ?? false, FILTER_VALIDATE_BOOL);

            if ($productId === '' && $qtyCt <= 0 && $qtyUn <= 0 && !$isFree) {
                continue;
            }

            $filtered[] = $item;
        }

        $this->merge(['items' => $filtered]);
    }

    public function rules(): array
    {
        return [
            'invoice_date' => 'required|date|after_or_equal:today',
            'customer_id' => 'required|exists:customers,id',
            'salesman' => 'nullable|string|max:150',
            'payment_type' => 'required|in:Cash,Credit',
            'payment_mode' => 'nullable|in:Cash,Credit,UPI,Cheque',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'paid_amount' => 'nullable|numeric|min:0|decimal:0,6',
            'po_no' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_ct' => 'nullable|numeric|min:0',
            'items.*.qty_un' => 'nullable|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'items.*.is_free' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_date.required' => 'The invoice date is required.',
            'invoice_date.after_or_equal' => 'Invoice date cannot be in the past',
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'payment_type.required' => 'Please select a payment type.',
            'payment_type.in' => 'The selected payment type is invalid.',
            'due_date.date' => 'Due date must be same or after invoice date',
            'due_date.after_or_equal' => 'Due date must be same or after invoice date',
            'items.required' => 'An invoice must have at least one item.',
            'items.min' => 'An invoice must have at least one item.',
            'items.*.product_id.required' => 'A product must be selected for each item.',
            'items.*.product_id.exists' => 'The selected product is invalid.',
            'items.*.qty_ct.numeric' => 'Carton quantity must be a number.',
            'items.*.qty_ct.min' => 'Carton quantity cannot be negative.',
            'items.*.qty_un.numeric' => 'Unit quantity must be a number.',
            'items.*.qty_un.min' => 'Unit quantity cannot be negative.',
            'items.*.discount_pct.numeric' => 'Discount percentage must be a number.',
            'items.*.discount_pct.min' => 'Discount cannot be negative.',
            'items.*.discount_pct.max' => 'Discount cannot exceed 100%.',
            'items.*.is_free.boolean' => 'The is_free flag must be true or false.',
        ];
    }
}
