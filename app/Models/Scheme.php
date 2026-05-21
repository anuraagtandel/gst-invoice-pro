<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Scheme extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'product_id',
        'slab_unit',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function schemeSlabs(): HasMany
    {
        return $this->hasMany(SchemeSlab::class);
    }

    public function scopeActive(Builder $query): void
    {
        $today = Carbon::today()->toDateString();
        
        $query->where('is_active', true)
              ->where(function (Builder $q) use ($today) {
                  $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $today);
              })
              ->where(function (Builder $q) use ($today) {
                  $q->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $today);
              });
    }
}
