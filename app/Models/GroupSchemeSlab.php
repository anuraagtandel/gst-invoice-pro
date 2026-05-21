<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupSchemeSlab extends Model
{
    protected $fillable = [
        'group_scheme_id',
        'min_qty_ct',
        'max_qty_ct',
        'free_qty_units',
    ];

    public function groupScheme(): BelongsTo
    {
        return $this->belongsTo(GroupScheme::class);
    }
}

