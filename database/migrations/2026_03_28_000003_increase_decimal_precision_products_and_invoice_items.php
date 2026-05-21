<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_rate', 12, 4)->nullable()->change();
            $table->decimal('base_price', 12, 4)->change();
            $table->decimal('trade_price', 12, 4)->nullable()->change();
        });

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->decimal('base_price', 12, 4)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_rate', 10, 2)->nullable()->change();
            $table->decimal('base_price', 10, 2)->change();
            $table->decimal('trade_price', 10, 2)->nullable()->change();
        });

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->decimal('base_price', 10, 2)->change();
            });
        }
    }
};

