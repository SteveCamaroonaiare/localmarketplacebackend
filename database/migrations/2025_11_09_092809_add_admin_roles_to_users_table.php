<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('admin_role', ['super_admin', 'admin'])->nullable();
            $table->boolean('is_active_admin')->default(false);
            $table->timestamp('admin_since')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admin_role', 'is_active_admin', 'admin_since', 'approved_by']);
        });
    }
};