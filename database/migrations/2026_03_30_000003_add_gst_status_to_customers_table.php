<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers') || Schema::hasColumn('customers', 'gst_status')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('gst_status', ['NON_GST', 'GST_REGISTERED', 'INVALID_GST'])->default('NON_GST')->after('gstin');
        });

        $regex = '^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$';

        DB::table('customers')->update([
            'gst_status' => DB::raw("CASE
                WHEN gstin IS NULL OR TRIM(gstin) = '' THEN 'NON_GST'
                WHEN UPPER(gstin) REGEXP '{$regex}' THEN 'GST_REGISTERED'
                ELSE 'INVALID_GST'
            END"),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'gst_status')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('gst_status');
        });
    }
};

