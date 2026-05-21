<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Salesman;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'invoice_date',
        'customer_id',
        'payment_type',
        'payment_mode',
        'due_date',
        'payment_status',
        'salesman',
        'salesman_id',
        'po_no',
        'tax_type',
        'subtotal_mrp',
        'total_discount',
        'taxable_amount',
        'total_cgst',
        'total_sgst',
        'total_cess',
        'free_goods_value',
        'round_off',
        'grand_total',
        'total_amount',
        'paid_amount',
        'pending_amount',
        'amount_in_words',
        'is_deleted',
        'einvoice_status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal_mrp' => 'float',
        'total_discount' => 'float',
        'taxable_amount' => 'float',
        'total_cgst' => 'float',
        'total_sgst' => 'float',
        'total_cess' => 'float',
        'free_goods_value' => 'float',
        'round_off' => 'float',
        'grand_total' => 'float',
        'total_amount' => 'decimal:6',
        'paid_amount' => 'decimal:6',
        'pending_amount' => 'decimal:6',
        'is_deleted' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function salesmanUser(): BelongsTo
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeActive(Builder $query): void
    {
        $table = $query->getModel()->getTable();
        $query->where($table . '.is_deleted', false);
    }

    public function scopeAccountingActive(Builder $query): void
    {
        $table = $query->getModel()->getTable();
        $query->where($table . '.is_deleted', false)->whereNull($table . '.deleted_at');
    }
}
