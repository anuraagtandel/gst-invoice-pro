<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('group_schemes')) {
            Schema::create('group_schemes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('product_group_id')->constrained('product_groups')->cascadeOnDelete();
                $table->foreignId('free_product_id')->constrained('products')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['name', 'product_group_id']);
            });
        }

        if (! Schema::hasTable('group_scheme_slabs')) {
            Schema::create('group_scheme_slabs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_scheme_id')->constrained('group_schemes')->cascadeOnDelete();
                $table->unsignedInteger('min_qty_ct');
                $table->unsignedInteger('max_qty_ct');
                $table->unsignedInteger('free_qty_units');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_scheme_slabs');
        Schema::dropIfExists('group_schemes');
    }
};

