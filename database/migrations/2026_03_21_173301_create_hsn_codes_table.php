<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hsn_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('hsn_codes')->insert([
            ['code' => '2201', 'description' => 'Packaged Water', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '2202', 'description' => 'Flavoured/Sweetened Water & Aerated Drinks', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '2009', 'description' => 'Fruit Juices', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
