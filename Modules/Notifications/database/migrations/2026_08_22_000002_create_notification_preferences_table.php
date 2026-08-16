<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-account notification preference overrides.
 *
 * Defaults (when no row exists): in-app = enabled, email = disabled.
 * A row is only created when a user explicitly changes a preference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();

            $table->string('account_type', 30);       // admin | staff | guardian
            $table->unsignedBigInteger('account_id');
            $table->string('portal', 20);
            $table->string('notification_type', 80);  // NotificationType constant value

            // In-app delivery: default true (no row = enabled)
            $table->boolean('enabled')->default(true);

            // Email delivery: default false until an email adapter is configured
            $table->boolean('email_enabled')->default(false);

            $table->timestamps();

            $table->unique(
                ['account_type', 'account_id', 'portal', 'notification_type'],
                'np_account_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
