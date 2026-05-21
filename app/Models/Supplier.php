<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'gstin',
        'pan',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'state_code',
        'pos_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
