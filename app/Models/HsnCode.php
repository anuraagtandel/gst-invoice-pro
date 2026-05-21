<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsnCode extends Model
{
    protected $fillable = ['code', 'description', 'is_active'];
}
