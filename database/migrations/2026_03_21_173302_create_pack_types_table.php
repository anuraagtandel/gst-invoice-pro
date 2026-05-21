<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pack_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('pack_types')->insert([
            ['code' => 'PET', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'RGP', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CAN', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'TETRA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_types');
    }
};
