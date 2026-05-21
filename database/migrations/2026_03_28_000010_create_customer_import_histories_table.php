<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_import_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('file_name');
            $table->string('stored_file_path')->nullable();
            $table->string('status')->default('preview');
            $table->string('duplicate_mode')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->json('errors')->nullable();
            $table->json('error_rows')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_import_histories');
    }
};

