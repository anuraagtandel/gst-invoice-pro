<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salesmen')) {
            return;
        }

        Schema::create('salesmen', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('mobile', 10)->nullable();
            $table->string('email', 150)->nullable();
            $table->unsignedBigInteger('area_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesmen');
    }
};

