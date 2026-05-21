<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->date('payment_date');
                $table->decimal('amount', 18, 6)->default(0);
                $table->enum('payment_mode', ['Cash', 'UPI', 'Cheque', 'Bank', 'Other'])->default('Cash');
                $table->string('reference_no')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_payment_allocations')) {
            Schema::create('customer_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_payment_id')->index();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->decimal('amount', 18, 6)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_allocations');
        Schema::dropIfExists('customer_payments');
    }
};
