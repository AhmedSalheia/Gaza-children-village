<?php

declare(strict_types=1);

namespace Modules\Imports\Enums;

/**
 * Per-row outcome status after validation or apply.
 */
enum RowResultStatus: string
{
    /** Row was applied and a new student record was created. */
    case Created = 'created';

    /** Row was applied and an existing student record was updated. */
    case Updated = 'updated';

    /** Row matched an existing student record with identical data — nothing to do. */
    case SkippedExisting = 'skipped_existing';

    /** Row matches an existing student but with conflicting values — requires manual review. */
    case Conflict = 'conflict';

    /** Row failed structural or reference validation (missing required fields, invalid values). */
    case Invalid = 'invalid';

    /** Row references an institution the applying actor is not authorized for. */
    case Unauthorized = 'unauthorized';

    /** Row passed validation but the domain action failed at apply time. */
    case Failed = 'failed';

    // -------------------------------------------------------------------

    /** Statuses that are considered errors (visible in result reports). */
    public static function errorStatuses(): array
    {
        return [self::Conflict, self::Invalid, self::Unauthorized, self::Failed];
    }

    public function isError(): bool
    {
        return in_array($this, self::errorStatuses(), true);
    }

    /** Statuses that block the Apply step for the affected row. */
    public function blocksApply(): bool
    {
        return in_array($this, [self::Conflict, self::Invalid, self::Unauthorized], true);
    }
}
