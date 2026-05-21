<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'qty_ct',
        'qty_un',
        'total_units',
        'purchase_rate',
        'item_total',
    ];

    protected $casts = [
        'qty_ct' => 'float',
        'qty_un' => 'float',
        'total_units' => 'float',
        'purchase_rate' => 'decimal:6',
        'item_total' => 'decimal:6',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
