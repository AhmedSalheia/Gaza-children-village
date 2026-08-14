<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Position-definition → role grant table.
 *
 * Maps a position_definition string to a role_id in Authorization.roles.
 * Both are stored as plain values (not FK constrained across module boundaries).
 *
 * This allows the PolicyKernel to resolve "what roles does a teacher hold?"
 * by looking up the position's role grant, without any cross-module ORM join.
 *
 * position_definition values must match Modules\Staff\Enums\PositionDefinition
 * case values (validated at the seeder level).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_role_grants', function (Blueprint $table): void {
            $table->id();

            // Matches PositionDefinition::value — no FK constraint (enum validation in seeder).
            $table->string('position_definition', 50);

            // References Authorization.roles — no FK constraint (cross-module boundary).
            $table->unsignedBigInteger('role_id');

            $table->string('granted_by', 200)->default('seeder');
            $table->timestamps();

            $table->unique(['position_definition', 'role_id'], 'position_role_grants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_role_grants');
    }
};
