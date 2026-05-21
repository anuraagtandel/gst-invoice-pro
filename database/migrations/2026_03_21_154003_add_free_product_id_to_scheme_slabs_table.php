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
        Schema::table('scheme_slabs', function (Blueprint $table) {
            $table->foreignId('free_product_id')->nullable()->after('free_qty')->constrained('products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_slabs', function (Blueprint $table) {
            $table->dropForeign(['free_product_id']);
            $table->dropColumn('free_product_id');
        });
    }
};
