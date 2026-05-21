<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity_in_units',
        'notes',
        'transaction_date',
        'reference_type',
        'reference_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
