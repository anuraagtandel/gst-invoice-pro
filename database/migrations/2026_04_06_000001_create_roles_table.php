<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();

        foreach (['Admin', 'Staff', 'Salesman'] as $name) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['description' => null, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

