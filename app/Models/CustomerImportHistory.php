<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerImportHistory extends Model
{
    protected $fillable = [
        'token',
        'file_name',
        'stored_file_path',
        'status',
        'duplicate_mode',
        'created_count',
        'updated_count',
        'imported_count',
        'failed_count',
        'duplicate_count',
        'errors',
        'error_rows',
        'uploaded_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'error_rows' => 'array',
        'uploaded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}

