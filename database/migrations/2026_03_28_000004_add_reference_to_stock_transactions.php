<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->string('reference_type')->nullable()->after('transaction_date');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropColumn(['reference_type', 'reference_id']);
        });
    }
};

