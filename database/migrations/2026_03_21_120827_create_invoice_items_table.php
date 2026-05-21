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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->boolean('is_free')->default(false);
            $table->string('product_description');
            $table->string('hsn_code');
            $table->decimal('mrp', 10, 2);
            $table->decimal('qty_ct', 10, 2)->default(0);
            $table->decimal('qty_un', 10, 2)->default(0);
            $table->decimal('total_units', 10, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('base_price', 10, 2);
            $table->decimal('taxable', 12, 2);
            $table->decimal('cgst_rate', 5, 2);
            $table->decimal('cgst', 12, 2);
            $table->decimal('sgst_rate', 5, 2);
            $table->decimal('sgst', 12, 2);
            $table->decimal('cess_rate', 5, 2)->default(0);
            $table->decimal('cess', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
