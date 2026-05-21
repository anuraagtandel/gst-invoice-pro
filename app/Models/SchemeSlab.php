<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeSlab extends Model
{
    protected $fillable = [
        'scheme_id',
        'min_qty',
        'max_qty',
        'free_qty',
        'free_product_id',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function freeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'free_product_id');
    }
}
