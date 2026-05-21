<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')->update([
            'name' => DB::raw('UPPER(name)'),
            'code' => DB::raw('UPPER(code)'),
            'gstin' => DB::raw('UPPER(gstin)'),
        ]);
    }

    public function down(): void
    {
    }
};

