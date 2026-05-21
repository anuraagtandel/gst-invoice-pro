<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_groups')) {
            Schema::create('product_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_group_product')) {
            Schema::create('product_group_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_group_id')->constrained('product_groups')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['product_group_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_group_product');
        Schema::dropIfExists('product_groups');
    }
};

