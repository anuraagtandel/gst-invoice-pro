<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('volume')->nullable();
            $table->integer('pack_size')->default(24);
            $table->decimal('mrp', 10, 2);
            $table->decimal('trade_price', 10, 2)->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('cess_rate', 5, 2)->default(0);
            $table->string('hsn_code');
            $table->string('category')->nullable();
            $table->string('promo_tag')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
