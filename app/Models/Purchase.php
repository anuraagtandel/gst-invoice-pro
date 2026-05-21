<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_no',
        'bill_date',
        'supplier_id',
        'supplier',
        'vehicle_number',
        'total_amount',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'total_amount' => 'decimal:6',
    ];

    protected function vehicleNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : strtoupper($value),
            set: fn ($value) => $value === null ? null : strtoupper($value),
        );
    }

    public function supplier_ref()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
