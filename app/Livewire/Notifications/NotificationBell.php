<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Notifications\Services\NotificationService;

/**
 * Portal-neutral notification bell Livewire component.
 *
 * Shows unread count and a dropdown of recent active notifications.
 * Supports all three portals (admin, staff, guardian) via the $portal property.
 *
 * Security: every read/dismiss action re-verifies that the acting user is the
 * notification recipient via NotificationService, which throws \RuntimeException
 * on mismatch (rendered as a 403 abort here).
 *
 * Usage in layouts:
 *   <livewire:notifications.notification-bell portal="admin" />
 *   <livewire:notifications.notification-bell portal="staff" />
 *   <livewire:notifications.notification-bell portal="guardian" />
 */
final class NotificationBell extends Component
{
    /** Which portal this bell is rendered in. */
    public string $portal = 'admin';

    /** Whether the dropdown is open. */
    public bool $open = false;

    public function mount(string $portal = 'admin'): void
    {
        if (! in_array($portal, ['admin', 'staff', 'guardian'], true)) {
            abort(400, 'Invalid portal.');
        }

        $this->portal = $portal;

        if ($this->resolveAccountId() === null) {
            // Not authenticated for this portal — render an empty bell silently
        }
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    public function toggleDropdown(): void
    {
        $this->open = ! $this->open;
    }

    public function closeDropdown(): void
    {
        $this->open = false;
    }

    public function markRead(int $notificationId): void
    {
        $accountId = $this->resolveAccountId();

        if ($accountId === null) {
            abort(403);
        }

        try {
            app(NotificationService::class)->markRead(
                notificationId: $notificationId,
                actorAccountType: $this->portal,
                actorAccountId: $accountId,
                portal: $this->portal,
            );
        } catch (\RuntimeException) {
            abort(403);
        }
    }

    public function markAllRead(): void
    {
        $accountId = $this->resolveAccountId();

        if ($accountId === null) {
            abort(403);
        }

        // Batch mark-read restricted to the acting user's own rows
        DB::table('portal_notifications')
            ->where('recipient_account_type', $this->portal)
            ->where('recipient_account_id', $accountId)
            ->where('portal', $this->portal)
            ->whereNull('read_at')
            ->whereNull('dismissed_at')
            ->update(['read_at' => now()]);
    }

    public function dismiss(int $notificationId): void
    {
        $accountId = $this->resolveAccountId();

        if ($accountId === null) {
            abort(403);
        }

        try {
            app(NotificationService::class)->dismiss(
                notificationId: $notificationId,
                actorAccountType: $this->portal,
                actorAccountId: $accountId,
                portal: $this->portal,
            );
        } catch (\RuntimeException) {
            abort(403);
        }
    }

    // -----------------------------------------------------------------
    // Event listener — external code can trigger a bell refresh
    // -----------------------------------------------------------------

    #[On('notification-sent')]
    public function refresh(): void
    {
        // Livewire re-renders the component when this event fires
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render(): View
    {
        $accountId = $this->resolveAccountId();

        $unreadCount = 0;
        $notifications = collect();

        if ($accountId !== null) {
            $svc = app(NotificationService::class);
            $unreadCount = $svc->unreadCount($this->portal, $accountId, $this->portal);

            if ($this->open) {
                $notifications = $svc->recent($this->portal, $accountId, $this->portal);
            }
        }

        return view('livewire.notifications.notification-bell', [
            'unreadCount' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function resolveAccountId(): ?int
    {
        $user = match ($this->portal) {
            'admin' => auth('admin')->user(),
            'staff' => auth('staff')->user(),
            'guardian' => auth('guardian')->user(),
            default => null,
        };

        return $user !== null ? (int) $user->getKey() : null;
    }
}
