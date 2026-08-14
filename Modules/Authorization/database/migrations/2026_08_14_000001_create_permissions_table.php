<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stable permission catalogue.
 *
 * Keys follow dot-notation: resource.action (e.g. "institution.create").
 * Keys are code-governed; application code must reference PermissionKey
 * constants, never raw strings (architecture test F17 enforces this).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('description', 300)->default('');
            $table->string('group', 60)->default('general');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
