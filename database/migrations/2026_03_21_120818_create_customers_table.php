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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('gstin')->nullable();
            $table->string('mobile');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->default('Daman');
            $table->string('state_code')->nullable();
            $table->string('pos_code')->nullable();
            $table->string('fssai_no')->nullable();
            $table->enum('tax_type', ['CGST_SGST', 'CGST_UTGST', 'IGST'])->default('CGST_SGST');
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
        Schema::dropIfExists('customers');
    }
};
