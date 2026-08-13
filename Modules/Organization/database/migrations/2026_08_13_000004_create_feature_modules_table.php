<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the feature_modules table.
 *
 * A feature module is a configurable GCV business capability (e.g. Academic
 * Management, Medical Services). This is distinct from a physical Laravel
 * package managed by nwidart/laravel-modules.
 *
 * Stable codes are the machine identifiers. is_active is lifecycle and
 * configuration availability, not authorization and not proof the business
 * feature has been implemented.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_modules', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_modules');
    }
};
