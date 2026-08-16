<?php

declare(strict_types=1);

namespace Modules\Notifications\Actions;

use Modules\Notifications\Data\NotificationType;
use Modules\Notifications\Services\NotificationService;

/**
 * Public action that workflow transitions call to fan out notifications.
 *
 * This is the boundary between the Workflow engine / domain modules and the
 * Notifications module internals. Callers import only this class (which is in
 * the public Actions/ namespace) rather than importing NotificationService
 * directly. This keeps the coupling surface minimal and testable.
 *
 * Usage example (from a Livewire component or service):
 *
 *   app(NotifyOnTransition::class)(
 *       notificationType: NotificationType::CORRECTION_REQUEST_APPROVED,
 *       recipientAccountType: 'guardian',
 *       recipientAccountId: $guardianAccountId,
 *       portal: 'guardian',
 *       messageKey: 'correction_request.approved',
 *       messageParams: ['student_name' => $studentName, 'request_id' => $requestId],
 *       subjectType: 'CorrectionRequest',
 *       subjectId: $requestId,
 *   );
 */
final class NotifyOnTransition
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  string  $notificationType  NotificationType constant
     * @param  string  $recipientAccountType  'admin' | 'staff' | 'guardian'
     * @param  string  $portal  'admin' | 'staff' | 'guardian'
     * @param  string  $messageKey  Translation key leaf (under notifications.*)
     * @param  array<string, scalar>  $messageParams
     * @param  string|null  $subjectType  Domain entity class name
     * @param  int|null  $subjectId  Domain entity primary key
     * @param  int  $priority  1 = low, 2 = normal, 3 = high, 4 = urgent
     * @param  string|null  $actionRouteIdentifier  Named route key (resolved server-side)
     */
    public function __invoke(
        string $notificationType,
        string $recipientAccountType,
        int $recipientAccountId,
        string $portal,
        string $messageKey,
        array $messageParams = [],
        ?string $subjectType = null,
        ?int $subjectId = null,
        int $priority = 2,
        ?string $actionRouteIdentifier = null,
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        // Guard: only known notification types may be dispatched
        if (! in_array($notificationType, NotificationType::all(), true)) {
            throw new \InvalidArgumentException("Unknown notification type: {$notificationType}");
        }

        $this->notifications->send(
            notificationType: $notificationType,
            recipientAccountType: $recipientAccountType,
            recipientAccountId: $recipientAccountId,
            portal: $portal,
            messageKey: $messageKey,
            messageParams: $messageParams,
            subjectType: $subjectType,
            subjectId: $subjectId,
            priority: $priority,
            actionRouteIdentifier: $actionRouteIdentifier,
            expiresAt: $expiresAt,
        );
    }
}
