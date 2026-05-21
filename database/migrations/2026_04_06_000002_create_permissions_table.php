<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('module');
                $table->string('action');
                $table->string('code')->unique();
                $table->timestamps();

                $table->unique(['module', 'action']);
            });
        }

        $modules = [
            'Dashboard',
            'Customers',
            'Products',
            'Suppliers',
            'Inventory',
            'Purchases',
            'Sales (Invoices)',
            'Receivables',
            'Accounts',
            'Reports',
            'Masters',
            'Settings',
            'Users',
        ];

        $actions = ['View', 'Add', 'Edit', 'Delete'];

        $now = now();

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $code = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $module));
                $code = trim($code, '_') . '.' . strtolower($action);

                DB::table('permissions')->updateOrInsert(
                    ['code' => $code],
                    ['module' => $module, 'action' => $action, 'created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

