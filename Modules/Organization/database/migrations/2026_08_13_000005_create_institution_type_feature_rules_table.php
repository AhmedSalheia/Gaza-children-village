<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the institution_type_feature_rules table.
 *
 * Each row declares the rule governing whether a feature module is available
 * to all institutions of a given institution type. Valid rule values are:
 *   'required'  — enabled by default; cannot be disabled per-institution (F06).
 *   'default'   — enabled by default; may be disabled per-institution (F06).
 *   'allowed'   — disabled by default; may be enabled per-institution (F06).
 *
 * Absence of a row means the feature is unavailable to that type.
 *
 * The rule column is a bounded string, not a database ENUM, to maintain
 * MySQL/MariaDB and SQLite compatibility and to allow inspection without
 * schema migrations when the catalogue changes.
 *
 * Foreign keys use RESTRICT to prevent accidental deletion of type/feature
 * records that have historical rule configuration.
 *
 * Institution-specific activation overrides belong to F06 and are not
 * implemented here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_type_feature_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_type_id')
                ->constrained('institution_types')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('feature_module_id')
                ->constrained('feature_modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('rule');
            $table->unique(['institution_type_id', 'feature_module_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_type_feature_rules');
    }
};
