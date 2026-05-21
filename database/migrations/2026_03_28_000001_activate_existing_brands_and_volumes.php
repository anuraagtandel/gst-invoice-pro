<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('brands')->where('is_active', false)->update(['is_active' => true]);
        DB::table('volumes')->where('is_active', false)->update(['is_active' => true]);
    }

    public function down(): void
    {
        DB::table('brands')->where('is_active', true)->update(['is_active' => false]);
        DB::table('volumes')->where('is_active', true)->update(['is_active' => false]);
    }
};

