<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'gstin',
        'pan',
        'gst_status',
        'mobile',
        'email',
        'address',
        'city',
        'district',
        'area_id',
        'state',
        'state_code',
        'pos_code',
        'pin_code',
        'country',
        'fssai_no',
        'tax_type',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:6',
    ];

    protected function businessName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => (string) ($attributes['name'] ?? '')
        );
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    protected function taxTypeAuto(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $gstin = $attributes['gstin'] ?? '';
                if (!empty($gstin) && str_starts_with($gstin, '26')) {
                    return 'CGST_UTGST';
                }
                return 'CGST_SGST';
            }
        );
    }
}
