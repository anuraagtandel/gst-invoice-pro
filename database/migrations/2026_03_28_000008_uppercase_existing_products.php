<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->update([
            'product_code' => DB::raw('UPPER(product_code)'),
            'name' => DB::raw('UPPER(name)'),
            'brand' => DB::raw('UPPER(brand)'),
            'volume' => DB::raw('UPPER(volume)'),
            'pack_type' => DB::raw('UPPER(pack_type)'),
            'category' => DB::raw('UPPER(category)'),
        ]);
    }

    public function down(): void
    {
    }
};

