<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model pointing to the Gaza civil-registry dataset table.
 *
 * Table name is resolved from config('civil-registry.table') at boot time so
 * the schema can be swapped without a code change.
 *
 * This model intentionally overrides all mutation methods to throw, enforcing
 * that the registry data is never modified through the application at runtime.
 * Writes go only through the civil-registry:import Artisan command which
 * uses DB::table() and upsert() directly.
 *
 * SECURITY: This model is never FK'd from People or Students tables.
 * A registry record is advisory only.
 */
final class CivilRegistryRecord extends Model
{
    /** Resolved at boot so the config is available. */
    protected $table = 'gaza_civil_records';

    /** @var array<string, string> */
    protected $casts = [
        'birth_date' => 'date',
        'is_deceased' => 'boolean',
    ];

    /** @var list<string> */
    protected $guarded = ['*'];

    public function getTable(): string
    {
        return config('civil-registry.table', 'gaza_civil_records');
    }

    // -------------------------------------------------------------------------
    // Read-only enforcement — override all mutation methods.
    // -------------------------------------------------------------------------

    /** @throws \LogicException always */
    public function save(array $options = []): bool
    {
        throw new \LogicException('CivilRegistryRecord is read-only; use the civil-registry:import command to load data.');
    }

    /** @throws \LogicException always */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('CivilRegistryRecord is read-only; use the civil-registry:import command to load data.');
    }

    /** @throws \LogicException always */
    public function delete(): ?bool
    {
        throw new \LogicException('CivilRegistryRecord is read-only and cannot be deleted through the application.');
    }

    /** @throws \LogicException always */
    public function forceDelete(): ?bool
    {
        throw new \LogicException('CivilRegistryRecord is read-only and cannot be deleted through the application.');
    }
}
