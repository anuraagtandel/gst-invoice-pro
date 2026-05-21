<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scheme_slabs', function (Blueprint $table) {
            if (Schema::hasColumn('scheme_slabs', 'buy_qty')) {
                $table->renameColumn('buy_qty', 'min_qty');
            }
        });
        Schema::table('scheme_slabs', function (Blueprint $table) {
            if (! Schema::hasColumn('scheme_slabs', 'max_qty')) {
                $table->integer('max_qty')->after('min_qty')->nullable()->comment('Null means no upper limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_slabs', function (Blueprint $table) {
            $table->renameColumn('min_qty', 'buy_qty');
            $table->dropColumn('max_qty');
        });
    }
};
