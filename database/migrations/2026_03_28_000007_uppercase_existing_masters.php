<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('unit_types')->update(['code' => DB::raw('UPPER(code)')]);
        DB::table('pack_types')->update(['code' => DB::raw('UPPER(code)')]);

        DB::table('brands')->update(['name' => DB::raw('UPPER(name)')]);
        DB::table('volumes')->update(['name' => DB::raw('UPPER(name)')]);
        DB::table('areas')->update(['name' => DB::raw('UPPER(name)')]);
        DB::table('categories')->update(['name' => DB::raw('UPPER(name)')]);

        DB::table('hsn_codes')->update(['description' => DB::raw('UPPER(description)')]);
    }

    public function down(): void
    {
    }
};

