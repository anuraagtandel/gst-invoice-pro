<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchases') || Schema::hasColumn('purchases', 'vehicle_number')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('vehicle_number', 50)->nullable()->after('supplier_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('purchases') || !Schema::hasColumn('purchases', 'vehicle_number')) {
            return;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('vehicle_number');
        });
    }
};

