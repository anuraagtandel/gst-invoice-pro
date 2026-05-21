<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            });
        }

        try {
            $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');
            if ($adminRoleId) {
                DB::table('users')->whereNull('role_id')->update(['role_id' => $adminRoleId]);
            }
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }
    }
};

