{{--
    Notification Bell dropdown component.
    Renders a bell icon with unread badge and a professional dropdown.
    Supports admin, staff, and guardian portals.
--}}

<div
    class="notification-bell"
    x-data="{ open: @entangle('open') }"
    x-on:keydown.escape.window="$wire.closeDropdown()"
>

    {{-- =========================================================
         BELL BUTTON
    ========================================================== --}}

    <button
        type="button"
        class="notification-bell__trigger"
        aria-label="{{ __('ui.notifications', [], null, 'Notifications') }}"
        aria-haspopup="true"
        :aria-expanded="open ? 'true' : 'false'"
        wire:click="toggleDropdown"
    >

        <span class="notification-bell__icon-wrapper">

            {{-- Bell Icon --}}
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
            >
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>

            {{-- =================================================
                 UNREAD BADGE
            ================================================== --}}

            @if($unreadCount > 0)

                <span
                    class="notification-bell__badge"
                    aria-label="{{ trans_choice(
                        '{1} :count unread notification|[2,*] :count unread notifications',
                        $unreadCount,
                        ['count' => $unreadCount]
                    ) }}"
                >
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>

            @endif

        </span>

    </button>


    {{-- =========================================================
         DROPDOWN
    ========================================================== --}}

    <div
        class="notification-bell__dropdown"

        x-show="open"

        x-cloak

        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"

        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"

        x-on:click.outside="$wire.closeDropdown()"

        role="dialog"
        aria-label="{{ __('ui.notifications', [], null, 'Notifications') }}"
    >

        {{-- =====================================================
             DROPDOWN HEADER
        ====================================================== --}}

        <div class="notification-bell__header">

            <div class="notification-bell__header-title">

                <span class="notification-bell__header-icon">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>

                </span>

                <span>
                    {{ __('ui.notifications', [], null, 'Notifications') }}
                </span>

                @if($unreadCount > 0)

                    <span class="notification-bell__header-count">
                        {{ $unreadCount }}
                    </span>

                @endif

            </div>


            {{-- Mark all as read --}}

            @if($unreadCount > 0)

                <button
                    type="button"
                    class="notification-bell__mark-all"
                    wire:click="markAllRead"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>

                    {{ __('ui.mark_all_read', [], null, 'Mark all read') }}

                </button>

            @endif

        </div>


        {{-- =====================================================
             NOTIFICATION LIST
        ====================================================== --}}

        @if($notifications->isEmpty())

            {{-- Empty State --}}

            <div class="notification-bell__empty">

                <div class="notification-bell__empty-icon">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                    >
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>

                </div>

                <div class="notification-bell__empty-title">
                    {{ __('ui.no_notifications', [], null, 'No new notifications') }}
                </div>

                <div class="notification-bell__empty-text">
                    {{ __('ui.notifications_empty_message', [], null, 'You are all caught up.') }}
                </div>

            </div>

        @else

            <ul
                class="notification-bell__list"
                role="list"
            >

                @foreach($notifications as $notification)

                    <li
                        class="
                            notification-bell__item
                            {{ $notification->read_at
                                ? 'notification-bell__item--read'
                                : 'notification-bell__item--unread'
                            }}
                        "
                        wire:key="notification-{{ $notification->id }}"
                    >

                        {{-- Notification status indicator --}}

                        <div class="notification-bell__status-icon">

                            @if($notification->read_at)

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>

                            @else

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>

                            @endif

                        </div>


                        {{-- Notification content --}}

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


                        {{-- Actions --}}

                        <div class="notification-bell__item-actions">

                            {{-- Mark as read --}}

                            @if($notification->read_at === null)

                                <button
                                    type="button"
                                    class="notification-bell__action notification-bell__action--read"
                                    wire:click="markRead({{ $notification->id }})"
                                    aria-label="{{ __('ui.mark_read', [], null, 'Mark as read') }}"
                                    title="{{ __('ui.mark_read', [], null, 'Mark as read') }}"
                                >

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>

                                </button>

                            @endif


                            {{-- Dismiss --}}

                            <button
                                type="button"
                                class="notification-bell__action notification-bell__action--dismiss"
                                wire:click="dismiss({{ $notification->id }})"
                                aria-label="{{ __('ui.dismiss', [], null, 'Dismiss') }}"
                                title="{{ __('ui.dismiss', [], null, 'Dismiss') }}"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M18 6L6 18"/>
                                    <path d="M6 6l12 12"/>
                                </svg>

                            </button>

                        </div>

                    </li>

                @endforeach

            </ul>

        @endif

    </div>

</div>


{{-- =============================================================
     NOTIFICATION BELL CSS
============================================================= --}}

<style>

    /* ---------------------------------------------------------
       Container
    --------------------------------------------------------- */

    .notification-bell {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }


    /* ---------------------------------------------------------
       Bell Button
       No white box / no background
    --------------------------------------------------------- */

    .notification-bell__trigger {

        position: relative;

        width: 42px;
        height: 42px;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 0;
        margin: 0;

        border: none;
        outline: none;

        background: transparent !important;

        color: #f4c430;

        cursor: pointer;

        -webkit-appearance: none;
        appearance: none;

        transition:
            transform .25s ease,
            color .25s ease;
    }


    .notification-bell__trigger:focus {
        outline: none;
        box-shadow: none;
    }


    /* ---------------------------------------------------------
       Icon Wrapper
    --------------------------------------------------------- */

    .notification-bell__icon-wrapper {

        position: relative;

        display: flex;
        align-items: center;
        justify-content: center;

        width: 30px;
        height: 30px;
    }


    /* ---------------------------------------------------------
       Bell Icon
    --------------------------------------------------------- */

    .notification-bell__icon {

        width: 25px;
        height: 25px;

        display: block;

        color: #f4c430;

        stroke: currentColor;

        filter:
            drop-shadow(
                0 2px 3px rgba(180, 130, 0, .25)
            );

        transform-origin: top center;

        transition:
            transform .25s cubic-bezier(.34, 1.56, .64, 1),
            color .25s ease,
            filter .25s ease;
    }


    /* ---------------------------------------------------------
       Hover
    --------------------------------------------------------- */

    .notification-bell__trigger:hover .notification-bell__icon {

        color: #eab308;

        transform:
            scale(1.28)
            rotate(0deg);

        filter:
            drop-shadow(
                0 4px 7px rgba(180, 130, 0, .35)
            );
    }


    /* ---------------------------------------------------------
       Bell Ring Animation
    --------------------------------------------------------- */

    .notification-bell__trigger:hover .notification-bell__icon {

        animation:
            notification-bell-ring .5s ease;
    }


    @keyframes notification-bell-ring {

        0% {
            transform: scale(1);
        }

        20% {
            transform: scale(1.28) rotate(8deg);
        }

        40% {
            transform: scale(1.28) rotate(-8deg);
        }

        60% {
            transform: scale(1.28) rotate(5deg);
        }

        80% {
            transform: scale(1.28) rotate(-3deg);
        }

        100% {
            transform: scale(1.28) rotate(0);
        }

    }


    /* ---------------------------------------------------------
       Unread Badge
    --------------------------------------------------------- */

    .notification-bell__badge {

        position: absolute;

        top: -4px;
        right: -5px;

        min-width: 18px;
        height: 18px;

        padding: 0 5px;

        display: flex;
        align-items: center;
        justify-content: center;

        border: 2px solid #fff;

        border-radius: 999px;

        background: #dc2626;

        color: #fff;

        font-size: 9px;
        font-weight: 800;

        line-height: 1;

        box-shadow:
            0 2px 6px rgba(0, 0, 0, .2);

        z-index: 5;
    }


    /* RTL badge */

    html[dir="rtl"] .notification-bell__badge {

        right: auto;
        left: -5px;
    }


    /* ---------------------------------------------------------
       Dropdown
    --------------------------------------------------------- */

    .notification-bell__dropdown {

        position: absolute;

        top: calc(100% + 12px);

        right: 0;

        width: 390px;
        max-width: calc(100vw - 24px);

        overflow: hidden;

        background: #fff;

        border: 1px solid #e5e7eb;

        border-radius: 14px;

        box-shadow:
            0 20px 45px rgba(15, 23, 42, .15),
            0 5px 12px rgba(15, 23, 42, .08);

        z-index: 9999;
    }


    /* RTL dropdown */

    html[dir="rtl"] .notification-bell__dropdown {

        right: auto;
        left: 0;
    }


    /* ---------------------------------------------------------
       Header
    --------------------------------------------------------- */

    .notification-bell__header {

        display: flex;

        align-items: center;
        justify-content: space-between;

        gap: 12px;

        padding: 15px 17px;

        background:
            linear-gradient(
                to bottom,
                #ffffff,
                #fafafa
            );

        border-bottom: 1px solid #eef0f2;
    }


    .notification-bell__header-title {

        display: flex;

        align-items: center;

        gap: 9px;

        color: #111827;

        font-size: 15px;

        font-weight: 700;
    }


    .notification-bell__header-icon {

        width: 30px;
        height: 30px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 8px;

        background: #fff7d6;

        color: #eab308;
    }


    .notification-bell__header-icon svg {

        width: 17px;
        height: 17px;
    }


    .notification-bell__header-count {

        min-width: 21px;
        height: 21px;

        padding: 0 6px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        border-radius: 999px;

        background: #dc2626;

        color: #fff;

        font-size: 10px;

        font-weight: 700;
    }


    /* ---------------------------------------------------------
       Mark All
    --------------------------------------------------------- */

    .notification-bell__mark-all {

        display: inline-flex;

        align-items: center;

        gap: 5px;

        padding: 5px 7px;

        border: none;

        background: transparent;

        color: #2563eb;

        font-size: 11px;

        font-weight: 600;

        cursor: pointer;

        transition: color .2s ease;
    }


    .notification-bell__mark-all:hover {

        color: #1d4ed8;

        text-decoration: underline;
    }


    .notification-bell__mark-all svg {

        width: 14px;
        height: 14px;
    }


    /* ---------------------------------------------------------
       Notification List
    --------------------------------------------------------- */

    .notification-bell__list {

        max-height: 430px;

        overflow-y: auto;

        margin: 0;
        padding: 0;

        list-style: none;
    }


    /* Scrollbar */

    .notification-bell__list::-webkit-scrollbar {

        width: 5px;
    }


    .notification-bell__list::-webkit-scrollbar-thumb {

        background: #d1d5db;

        border-radius: 999px;
    }


    /* ---------------------------------------------------------
       Notification Item
    --------------------------------------------------------- */

    .notification-bell__item {

        position: relative;

        display: flex;

        align-items: flex-start;

        gap: 11px;

        padding: 14px 15px;

        border-bottom: 1px solid #f1f5f9;

        transition:
            background .2s ease;
    }


    .notification-bell__item:last-child {

        border-bottom: none;
    }


    .notification-bell__item:hover {

        background: #f8fafc;
    }


    /* Unread */

    .notification-bell__item--unread {

        background: #fffcf0;
    }


    .notification-bell__item--unread:hover {

        background: #fff9df;
    }


    /* ---------------------------------------------------------
       Status Icon
    --------------------------------------------------------- */

    .notification-bell__status-icon {

        flex: 0 0 34px;

        width: 34px;
        height: 34px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 10px;

        background: #f3f4f6;

        color: #9ca3af;
    }


    .notification-bell__item--unread
    .notification-bell__status-icon {

        background: #fff3bf;

        color: #eab308;
    }


    .notification-bell__status-icon svg {

        width: 17px;
        height: 17px;
    }


    /* ---------------------------------------------------------
       Body
    --------------------------------------------------------- */

    .notification-bell__item-body {

        min-width: 0;

        flex: 1;
    }


    .notification-bell__message {

        margin: 0;

        color: #374151;

        font-size: 13px;

        line-height: 1.6;

        font-weight: 500;
    }


    .notification-bell__item--unread
    .notification-bell__message {

        color: #111827;

        font-weight: 600;
    }


    .notification-bell__time {

        display: block;

        margin-top: 5px;

        color: #9ca3af;

        font-size: 10px;

        line-height: 1.3;
    }


    /* ---------------------------------------------------------
       Actions
    --------------------------------------------------------- */

    .notification-bell__item-actions {

        display: flex;

        align-items: center;

        gap: 3px;

        opacity: 0;

        transition: opacity .2s ease;
    }


    .notification-bell__item:hover
    .notification-bell__item-actions {

        opacity: 1;
    }


    .notification-bell__action {

        width: 27px;
        height: 27px;

        display: flex;

        align-items: center;
        justify-content: center;

        padding: 0;

        border: none;

        border-radius: 7px;

        background: transparent;

        cursor: pointer;

        transition:
            background .2s ease,
            color .2s ease;
    }


    .notification-bell__action svg {

        width: 14px;
        height: 14px;
    }


    .notification-bell__action--read {

        color: #16a34a;
    }


    .notification-bell__action--read:hover {

        background: #dcfce7;

        color: #15803d;
    }


    .notification-bell__action--dismiss {

        color: #9ca3af;
    }


    .notification-bell__action--dismiss:hover {

        background: #fee2e2;

        color: #dc2626;
    }


    /* ---------------------------------------------------------
       Empty State
    --------------------------------------------------------- */

    .notification-bell__empty {

        padding: 45px 25px;

        text-align: center;
    }


    .notification-bell__empty-icon {

        width: 58px;
        height: 58px;

        margin: 0 auto 13px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: #fff7d6;

        color: #eab308;
    }


    .notification-bell__empty-icon svg {

        width: 28px;
        height: 28px;
    }


    .notification-bell__empty-title {

        color: #374151;

        font-size: 14px;

        font-weight: 700;
    }


    .notification-bell__empty-text {

        margin-top: 5px;

        color: #9ca3af;

        font-size: 11px;
    }


    /* ---------------------------------------------------------
       Mobile
    --------------------------------------------------------- */

    @media (max-width: 576px) {

        .notification-bell__dropdown {

            position: fixed;

            top: 70px;

            left: 12px;
            right: 12px;

            width: auto;

            max-width: none;
        }

    }


    /* ---------------------------------------------------------
       Alpine x-cloak
    --------------------------------------------------------- */

    [x-cloak] {

        display: none !important;
    }

</style>

