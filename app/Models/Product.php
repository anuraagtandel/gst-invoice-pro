<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_code',
        'name',
        'brand',
        'volume',
        'unit_type',
        'pack_type',
        'pack_size',
        'mrp',
        'purchase_rate',
        'trade_price',
        'base_price',
        'gst_rate',
        'cess_rate',
        'hsn_code',
        'category',
        'promo_tag',
        'notes',
        'is_active',
        'opening_stock_units',
    ];

    protected $casts = [
        'mrp' => 'float',
        'purchase_rate' => 'decimal:6',
        'trade_price' => 'decimal:6',
        'base_price' => 'decimal:6',
        'gst_rate' => 'float',
        'cess_rate' => 'float',
        'is_active' => 'boolean',
    ];

    public function schemes(): HasMany
    {
        return $this->hasMany(Scheme::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function openingStock(): HasOne
    {
        return $this->hasOne(OpeningStock::class);
    }

    public function productGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProductGroup::class, 'product_group_product')
            ->withTimestamps();
    }

    public function getCurrentStockUnitsAttribute(): int
    {
        $openingUnits = $this->opening_stock_units ?? 0;

        $purchasedUnits = $this->stockTransactions()->where('type', 'purchase')->sum('quantity_in_units');
        $soldUnits = $this->stockTransactions()->where('type', 'sale')->sum('quantity_in_units');
        $adjustedUnits = $this->stockTransactions()->where('type', 'adjustment')->sum('quantity_in_units');

        return (int) ($openingUnits + $purchasedUnits - $soldUnits + $adjustedUnits);
    }

    public function getFormattedStockAttribute(): string
    {
        $units = $this->current_stock_units;
        $ct = floor($units / $this->pack_size);
        $un = $units % $this->pack_size;
        
        $parts = [];
        if ($ct > 0) $parts[] = "{$ct} CT";
        if ($un > 0 || empty($parts)) $parts[] = "{$un} UN";
        
        return implode(' & ', $parts);
    }

    protected function descriptionLabel(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim((string) ($attributes['name'] ?? ''))
        );
    }

    protected function baseFromTrade(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if (empty($attributes['trade_price'])) {
                    return 0.000000;
                }
                $rate = $attributes['trade_price'] / (1 + ($attributes['gst_rate'] / 100));
                return round($rate, 6);
            }
        );
    }
}
