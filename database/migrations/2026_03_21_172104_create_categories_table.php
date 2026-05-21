<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default categories
        DB::table('categories')->insert([
            ['name' => 'Carbonated Drinks', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Soda / Seltzer', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Packaged Water', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Juice / Fruit Drinks', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Energy Drinks', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
