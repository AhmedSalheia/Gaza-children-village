{{--
    Notification Bell dropdown component.
    Renders a bell icon with unread badge, and a dropdown of recent notifications.
    Supports admin, staff, and guardian portals via the $portal property.
--}}
<div
    class="notification-bell"
    x-data="{ open: @entangle('open') }"
    x-on:keydown.escape.window="$wire.closeDropdown()"
>
    {{-- Bell button --}}
    <button
        type="button"
        class="notification-bell__trigger"
        aria-label="{{ __('ui.notifications', [], null, 'Notifications') }}"
        aria-haspopup="true"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        wire:click="toggleDropdown"
    >
        {{-- Bell icon (inline SVG, no external dependencies) --}}
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="notification-bell__icon"
            aria-hidden="true"
            width="20"
            height="20"
        >
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>

        {{-- Unread count badge --}}
        @if($unreadCount > 0)
        <span
            class="notification-bell__badge"
            aria-label="{{ trans_choice('{1} :count unread notification|[2,*] :count unread notifications', $unreadCount, ['count' => $unreadCount]) }}"
        >
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        class="notification-bell__dropdown"
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:click.outside="$wire.closeDropdown()"
        role="dialog"
        aria-label="{{ __('ui.notifications', [], null, 'Notifications') }}"
    >
        {{-- Header --}}
        <div class="notification-bell__header">
            <span class="notification-bell__title">
                {{ __('ui.notifications', [], null, 'Notifications') }}
            </span>

            @if($unreadCount > 0)
            <button
                type="button"
                class="notification-bell__mark-all"
                wire:click="markAllRead"
            >
                {{ __('ui.mark_all_read', [], null, 'Mark all read') }}
            </button>
            @endif
        </div>

        {{-- Notification list --}}
        @if($notifications->isEmpty())
        <div class="notification-bell__empty">
            {{ __('ui.no_notifications', [], null, 'No new notifications') }}
        </div>
        @else
        <ul class="notification-bell__list" role="list">
            @foreach($notifications as $notification)
            <li
                class="notification-bell__item {{ $notification->read_at ? 'notification-bell__item--read' : 'notification-bell__item--unread' }}"
                wire:key="notification-{{ $notification->id }}"
            >
                <div class="notification-bell__item-body">
                    <p class="notification-bell__message">
                        {{ __('notifications.' . $notification->message_key, $notification->message_params ?? []) }}
                    </p>
                    <time
                        class="notification-bell__time"
                        datetime="{{ $notification->created_at->toIso8601String() }}"
                        title="{{ $notification->created_at->toDateTimeString() }}"
                    >
                        {{ $notification->created_at->diffForHumans() }}
                    </time>
                </div>

                <div class="notification-bell__item-actions">
                    @if($notification->read_at === null)
                    <button
                        type="button"
                        class="notification-bell__action"
                        wire:click="markRead({{ $notification->id }})"
                        aria-label="{{ __('ui.mark_read', [], null, 'Mark as read') }}"
                    >
                        <span aria-hidden="true">✓</span>
                    </button>
                    @endif

                    <button
                        type="button"
                        class="notification-bell__action notification-bell__action--dismiss"
                        wire:click="dismiss({{ $notification->id }})"
                        aria-label="{{ __('ui.dismiss', [], null, 'Dismiss') }}"
                    >
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
