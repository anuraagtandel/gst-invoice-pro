<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'is_free',
        'product_description',
        'hsn_code',
        'mrp',
        'qty_ct',
        'qty_un',
        'total_units',
        'discount_pct',
        'base_price',
        'taxable',
        'cgst_rate',
        'cgst',
        'sgst_rate',
        'sgst',
        'cess_rate',
        'cess',
        'line_total',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'mrp' => 'float',
        'qty_ct' => 'float',
        'qty_un' => 'float',
        'total_units' => 'float',
        'discount_pct' => 'float',
        'base_price' => 'decimal:6',
        'taxable' => 'float',
        'cgst_rate' => 'float',
        'cgst' => 'float',
        'sgst_rate' => 'float',
        'sgst' => 'float',
        'cess_rate' => 'float',
        'cess' => 'float',
        'line_total' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
