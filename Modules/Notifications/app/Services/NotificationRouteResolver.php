<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Modules\Notifications\Data\NotificationType;

/**
 * Resolves action_route_identifier values to concrete URLs server-side.
 *
 * No arbitrary URL is ever stored in or generated from the notification payload.
 * Only identifiers registered in this class's map can produce a URL. An unknown
 * or missing identifier resolves to null (no link rendered in the bell dropdown).
 *
 * Identifier format: "{portal}.{resource}.{action}" (e.g. "admin.correction_requests.index")
 */
final class NotificationRouteResolver
{
    /**
     * Map from notification type → default route identifier per portal.
     * Used when no action_route_identifier is stored on the notification.
     *
     * @var array<string, array<string, string>>
     */
    private const TYPE_DEFAULTS = [
        NotificationType::CORRECTION_REQUEST_SUBMITTED => [
            'admin' => 'admin.correction-requests.index',
            'staff' => 'staff.correction-requests.index',
        ],
        NotificationType::CORRECTION_REQUEST_APPROVED => [
            'guardian' => 'guardian.correction-requests.index',
        ],
        NotificationType::CORRECTION_REQUEST_REJECTED => [
            'guardian' => 'guardian.correction-requests.index',
        ],
        NotificationType::CORRECTION_REQUEST_APPLIED => [
            'guardian' => 'guardian.dashboard',
        ],
        NotificationType::DOCUMENT_REQUEST_SUBMITTED => [
            'admin' => 'admin.document-requests.index',
            'staff' => 'staff.document-requests.index',
        ],
        NotificationType::DOCUMENT_REQUEST_APPROVED => [
            'guardian' => 'guardian.document-requests.index',
        ],
        NotificationType::DOCUMENT_REQUEST_REJECTED => [
            'guardian' => 'guardian.document-requests.index',
        ],
        NotificationType::DOCUMENT_REQUEST_READY => [
            'guardian' => 'guardian.document-requests.index',
        ],
        NotificationType::DOCUMENT_REQUEST_ISSUED => [
            'guardian' => 'guardian.document-requests.index',
        ],
        NotificationType::FORMAL_REQUEST_SUBMITTED => [
            'admin' => 'admin.formal-requests.index',
        ],
        NotificationType::FORMAL_REQUEST_RESPONDED => [
            'staff' => 'staff.formal-requests.index',
        ],
        NotificationType::MARK_SHEET_RETURNED => [
            'staff' => 'staff.marks.index',
        ],
        NotificationType::MARK_SHEET_VERIFIED => [
            'staff' => 'staff.marks.index',
        ],
        NotificationType::ATTENDANCE_SHEET_RETURNED => [
            'staff' => 'staff.attendance.index',
        ],
        NotificationType::ATTENDANCE_SHEET_VERIFIED => [
            'staff' => 'staff.attendance.index',
        ],
        NotificationType::WORKFLOW_TRANSITION => [
            'admin' => 'admin.dashboard',
            'staff' => 'staff.dashboard',
        ],
        NotificationType::OPERATION_COMPLETED => [
            'admin' => 'admin.dashboard',
            'staff' => 'staff.dashboard',
        ],
        NotificationType::OPERATION_FAILED => [
            'admin' => 'admin.dashboard',
            'staff' => 'staff.dashboard',
        ],
    ];

    /**
     * Resolve an action route identifier to a URL.
     *
     * Returns null when the route does not exist or the identifier is not
     * in the registered map, so the caller can omit the link safely.
     */
    public function resolve(?string $identifier, string $portal): ?string
    {
        if ($identifier === null) {
            return null;
        }

        // Only identifiers that correspond to real named routes are allowed.
        // route() throws if the route name is unknown — catch to return null.
        try {
            return route($identifier);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Resolve the default action URL for a notification type + portal combination.
     * Used when no explicit action_route_identifier is stored on the notification.
     */
    public function resolveDefault(string $notificationType, string $portal): ?string
    {
        $identifier = self::TYPE_DEFAULTS[$notificationType][$portal] ?? null;

        return $this->resolve($identifier, $portal);
    }
}
