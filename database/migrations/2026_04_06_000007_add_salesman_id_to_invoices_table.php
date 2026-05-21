<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasTable('salesmen')) {
            return;
        }

        if (! Schema::hasColumn('invoices', 'salesman_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('salesman_id')->nullable()->after('salesman')->constrained('salesmen')->nullOnDelete();
            });
        }

        try {
            $rows = DB::table('invoices')
                ->whereNull('salesman_id')
                ->whereNotNull('salesman')
                ->select('id', 'salesman')
                ->get();

            foreach ($rows as $r) {
                $name = trim((string) ($r->salesman ?? ''));
                if ($name === '') {
                    continue;
                }
                $sid = DB::table('salesmen')
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->value('id');
                if ($sid) {
                    DB::table('invoices')->where('id', $r->id)->update(['salesman_id' => $sid]);
                }
            }
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        if (Schema::hasColumn('invoices', 'salesman_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('salesman_id');
            });
        }
    }
};

