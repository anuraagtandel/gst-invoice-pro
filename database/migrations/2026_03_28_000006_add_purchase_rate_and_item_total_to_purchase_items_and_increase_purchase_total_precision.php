<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'purchase_rate')) {
                $table->decimal('purchase_rate', 12, 4)->default(0)->after('total_units');
            }
            if (!Schema::hasColumn('purchase_items', 'item_total')) {
                $table->decimal('item_total', 12, 4)->default(0)->after('purchase_rate');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'item_total')) {
                $table->dropColumn('item_total');
            }
            if (Schema::hasColumn('purchase_items', 'purchase_rate')) {
                $table->dropColumn('purchase_rate');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->default(0)->change();
        });
    }
};

