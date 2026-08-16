<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\MarkWindowStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkEntryWindow;

/**
 * Extend an open mark-entry window to a new closes_at timestamp.
 *
 * Enforced rules:
 *  1. Window must currently be in an open/extended status.
 *  2. new_closes_at must be after the current closes_at.
 *  3. A reason and actor reference are required for the audit history.
 *
 * Authorization (must hold mark_window.extend) is the caller's responsibility.
 */
final class ExtendMarkWindow
{
    public function __invoke(
        MarkEntryWindow $window,
        \DateTimeInterface $newClosesAt,
        string $reason,
        string $actorRef,
    ): MarkEntryWindow {
        if (! $window->status->canExtend()) {
            throw new MarksException(
                "Window #{$window->id} cannot be extended: current status is '{$window->status->value}'."
            );
        }

        if ($newClosesAt <= $window->closes_at) {
            throw new MarksException(
                'new_closes_at must be after the current closes_at '.
                "({$window->closes_at->toDateTimeString()})."
            );
        }

        if (trim($reason) === '') {
            throw new MarksException('An extension reason is required.');
        }

        // Append to history
        $history = $window->extension_history ?? [];
        $history[] = [
            'extended_at' => now()->toDateTimeString(),
            'new_closes_at' => $newClosesAt->format('Y-m-d H:i:s'),
            'reason' => $reason,
            'actor_ref' => $actorRef,
        ];

        $window->closes_at = $newClosesAt->format('Y-m-d H:i:s');
        $window->status = MarkWindowStatus::Extended->value;
        $window->extension_history = $history;
        $window->save();

        return $window;
    }
}
