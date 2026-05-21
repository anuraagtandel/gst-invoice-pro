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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('payment_mode', ['Cash', 'Credit', 'UPI', 'Cheque'])->default('Cash');
            $table->string('salesman')->nullable();
            $table->string('po_no')->nullable();
            $table->enum('tax_type', ['CGST_SGST', 'CGST_UTGST', 'IGST']);
            $table->decimal('subtotal_mrp', 12, 2);
            $table->decimal('total_discount', 12, 2);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('total_cgst', 12, 2);
            $table->decimal('total_sgst', 12, 2);
            $table->decimal('total_cess', 12, 2);
            $table->decimal('free_goods_value', 12, 2);
            $table->decimal('round_off', 6, 2);
            $table->decimal('grand_total', 12, 2);
            $table->text('amount_in_words')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
