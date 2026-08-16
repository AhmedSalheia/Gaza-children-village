<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal notifications table.
 *
 * Named 'portal_notifications' (not 'notifications') to avoid collision with
 * Laravel's built-in notification storage table used by the Notifiable trait's
 * DatabaseChannel. This module manages its own custom notification records with
 * a different schema optimised for the GCV DATA portal UX (bell component,
 * translation-key-based messages, server-side route resolution).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_notifications', function (Blueprint $table): void {
            $table->id();

            // Recipient identity — which portal account owns this notification
            $table->string('recipient_account_type', 30);  // admin | staff | guardian
            $table->unsignedBigInteger('recipient_account_id');
            $table->string('portal', 20);                  // admin | staff | guardian

            // Notification type (stable enum value from NotificationType)
            $table->string('notification_type', 80);

            // Message: translation key only — no raw user-supplied text stored
            $table->string('message_key', 120);

            // Safe interpolation params (no sensitive values; enforced by NotificationService)
            $table->json('message_params')->nullable();

            // Subject reference — links back to the workflow/domain entity
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Priority: 1 = low, 2 = normal (default), 3 = high, 4 = urgent
            $table->unsignedTinyInteger('priority')->default(2);

            // Action route identifier — stable key resolved server-side by
            // NotificationRouteResolver; never an arbitrary URL from payload
            $table->string('action_route_identifier', 100)->nullable();

            // Lifecycle timestamps (append-only: read_at/dismissed_at are set once)
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // No updated_at — append-only; read/dismiss use dedicated columns

            // Indexes for the bell component's most common queries
            $table->index(
                ['recipient_account_type', 'recipient_account_id', 'portal', 'read_at'],
                'pn_recipient_unread_idx'
            );
            $table->index(
                ['recipient_account_type', 'recipient_account_id', 'portal', 'dismissed_at'],
                'pn_recipient_dismissed_idx'
            );
            $table->index(['notification_type'], 'pn_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
    }
};
