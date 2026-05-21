<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningStock extends Model
{
    protected $fillable = [
        'product_id',
        'opening_ct',
        'opening_un',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
