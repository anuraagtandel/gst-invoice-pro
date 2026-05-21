<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('salesmen')) {
            return;
        }

        if (! Schema::hasColumn('users', 'salesman_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('salesman_id')->nullable()->after('role_id')->constrained('salesmen')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'salesman_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('salesman_id');
            });
        }
    }
};

