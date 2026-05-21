<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'pan')) {
                $table->string('pan', 10)->nullable()->after('gstin');
            }
            if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email', 150)->nullable()->after('mobile');
            }
            if (!Schema::hasColumn('customers', 'district')) {
                $table->string('district', 100)->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'pin_code')) {
                $table->string('pin_code', 6)->nullable()->after('pos_code');
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country', 100)->nullable()->after('pin_code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('customers', 'pin_code')) {
                $table->dropColumn('pin_code');
            }
            if (Schema::hasColumn('customers', 'district')) {
                $table->dropColumn('district');
            }
            if (Schema::hasColumn('customers', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('customers', 'pan')) {
                $table->dropColumn('pan');
            }
        });
    }
};

