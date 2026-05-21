<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductGroup extends Model
{
    protected $fillable = ['name', 'description'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_group_product')
            ->withTimestamps();
    }
}

