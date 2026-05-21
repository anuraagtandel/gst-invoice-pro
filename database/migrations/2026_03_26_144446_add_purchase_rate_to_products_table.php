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
        if (!Schema::hasTable('products') || Schema::hasColumn('products', 'purchase_rate')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_rate', 10, 2)->nullable()->after('mrp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'purchase_rate')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('purchase_rate');
        });
    }
};
