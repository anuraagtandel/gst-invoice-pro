<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackSize extends Model
{
    protected $fillable = [
        'units_per_ct',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
