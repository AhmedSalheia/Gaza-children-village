<?php

declare(strict_types=1);

namespace Modules\Imports\Enums;

/**
 * Lifecycle statuses for an ImportBatch.
 *
 * Flow:
 *   uploaded → parsing → ready_for_mapping → validating → ready_for_review
 *            → applying → completed | completed_with_errors | failed
 *
 * cancelled is terminal and can be reached from any non-terminal state.
 */
enum BatchStatus: string
{
    /** File received, not yet parsed. */
    case Uploaded = 'uploaded';

    /** Row extraction in progress. */
    case Parsing = 'parsing';

    /** Parsing done; awaiting column-mapping configuration. */
    case ReadyForMapping = 'ready_for_mapping';

    /** Column mapping saved; row validation in progress. */
    case Validating = 'validating';

    /** Validation complete; awaiting human review and apply confirmation. */
    case ReadyForReview = 'ready_for_review';

    /** Apply in progress. */
    case Applying = 'applying';

    /** All valid rows applied successfully. */
    case Completed = 'completed';

    /** Apply finished but at least one row resulted in a non-fatal error. */
    case CompletedWithErrors = 'completed_with_errors';

    /** Apply aborted due to a hard infrastructure failure. */
    case Failed = 'failed';

    /** Operator cancelled the batch. Terminal. */
    case Cancelled = 'cancelled';

    // -------------------------------------------------------------------

    /** @return list<self> */
    public static function terminal(): array
    {
        return [self::Completed, self::CompletedWithErrors, self::Failed, self::Cancelled];
    }

    public function isTerminal(): bool
    {
        return in_array($this, self::terminal(), true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Uploaded => $next === self::Parsing || $next === self::Cancelled,
            self::Parsing => $next === self::ReadyForMapping || $next === self::Failed || $next === self::Cancelled,
            self::ReadyForMapping => $next === self::Validating || $next === self::Cancelled,
            self::Validating => $next === self::ReadyForReview || $next === self::Failed || $next === self::Cancelled,
            self::ReadyForReview => $next === self::Applying || $next === self::Cancelled,
            self::Applying => $next === self::Completed || $next === self::CompletedWithErrors || $next === self::Failed,
            default => false,
        };
    }
}
