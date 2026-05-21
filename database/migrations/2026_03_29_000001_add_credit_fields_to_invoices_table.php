<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('payment_type', ['Cash', 'Credit'])->default('Cash')->after('customer_id');
            $table->date('due_date')->nullable()->after('payment_type');
            $table->enum('payment_status', ['Paid', 'Unpaid'])->default('Paid')->after('due_date');
            $table->decimal('total_amount', 18, 6)->default(0)->after('grand_total');
            $table->decimal('paid_amount', 18, 6)->default(0)->after('total_amount');
            $table->decimal('pending_amount', 18, 6)->default(0)->after('paid_amount');
        });

        DB::table('invoices')->update([
            'payment_type' => DB::raw("CASE WHEN payment_mode = 'Credit' THEN 'Credit' ELSE 'Cash' END"),
            'payment_status' => DB::raw("CASE WHEN payment_mode = 'Credit' THEN 'Unpaid' ELSE 'Paid' END"),
            'total_amount' => DB::raw('grand_total'),
            'paid_amount' => DB::raw("CASE WHEN payment_mode = 'Credit' THEN 0 ELSE grand_total END"),
            'pending_amount' => DB::raw("CASE WHEN payment_mode = 'Credit' THEN grand_total ELSE 0 END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'due_date',
                'payment_status',
                'total_amount',
                'paid_amount',
                'pending_amount',
            ]);
        });
    }
};

