<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupScheme extends Model
{
    protected $fillable = [
        'name',
        'product_group_id',
        'free_product_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function freeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'free_product_id');
    }

    public function slabs(): HasMany
    {
        return $this->hasMany(GroupSchemeSlab::class)->orderBy('min_qty_ct');
    }
}

