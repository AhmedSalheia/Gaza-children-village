<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Password reset token tables for the three portal account types.
 *
 * These tables are infrastructure for F11 (password setup and recovery).
 * No recovery endpoints or token delivery are implemented in F09.
 * The password brokers reference these tables in config/auth.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_password_reset_tokens', function (Blueprint $table): void {
            $table->string('identifier')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('staff_password_reset_tokens', function (Blueprint $table): void {
            $table->string('identifier')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('guardian_password_reset_tokens', function (Blueprint $table): void {
            $table->string('identifier')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::dropIfExists('staff_password_reset_tokens');
        Schema::dropIfExists('guardian_password_reset_tokens');
    }
};
