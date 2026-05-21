<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers') || Schema::hasColumn('customers', 'credit_limit')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 18, 6)->nullable()->after('tax_type');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'credit_limit')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });
    }
};

