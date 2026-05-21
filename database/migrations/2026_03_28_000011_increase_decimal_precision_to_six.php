<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_rate', 18, 6)->nullable()->change();
            $table->decimal('base_price', 18, 6)->change();
            $table->decimal('trade_price', 18, 6)->nullable()->change();
        });

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->decimal('base_price', 18, 6)->change();
            });
        }

        if (Schema::hasTable('purchase_items') && Schema::hasColumn('purchase_items', 'purchase_rate')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->decimal('purchase_rate', 18, 6)->default(0)->change();
                if (Schema::hasColumn('purchase_items', 'item_total')) {
                    $table->decimal('item_total', 18, 6)->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'total_amount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('total_amount', 18, 6)->default(0)->change();
            });
        }
    }

    public function down(): void
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

        if (Schema::hasTable('purchase_items') && Schema::hasColumn('purchase_items', 'purchase_rate')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->decimal('purchase_rate', 12, 4)->default(0)->change();
                if (Schema::hasColumn('purchase_items', 'item_total')) {
                    $table->decimal('item_total', 12, 4)->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'total_amount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('total_amount', 12, 4)->default(0)->change();
            });
        }
    }
};

