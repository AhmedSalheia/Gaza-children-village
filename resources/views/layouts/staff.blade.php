<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.staff_portal')) — GCV DATA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="gcv-layout gcv-layout--staff">

<a href="#main-content" class="skip-link">{{ __('ui.skip_to_content', [], null, 'Skip to main content') }}</a>

<header class="portal-header" role="banner">
    <div class="portal-header__inner">
        <a href="{{ route('staff.dashboard') }}" class="portal-header__brand brand-mark h-full" aria-label="GCV DATA — {{ __('auth.staff_portal') }}">
            <img src="{{ asset('assets/img/gcv-logo-dark.png') }}" class="h-full" alt="GCV logo" />
        </a>

        <nav class="portal-header__nav" role="navigation" aria-label="{{ __('auth.staff_portal') }}">
            @auth('staff')
                @php
                    /**
                     * Compute the authenticated staff member's permission set for nav rendering.
                     * Permission chain: staff_accounts → staff_profiles → staff_positions (active)
                     * → position_role_grants → role_permissions → permissions
                     */
                    $staffAccount = auth('staff')->user();
                    $navPermissions = ($staffAccount && $staffAccount->staff_profile_id)
                        ? \Illuminate\Support\Facades\DB::table('staff_positions as pos')
                            ->join('position_role_grants as prg', 'prg.position_definition', '=', 'pos.position_definition')
                            ->join('role_permissions as rp', 'rp.role_id', '=', 'prg.role_id')
                            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                            ->where('pos.staff_profile_id', $staffAccount->staff_profile_id)
                            ->where('pos.started_on', '<=', now()->toDateString())
                            ->where(fn ($q) => $q->whereNull('pos.ended_on')->orWhere('pos.ended_on', '>=', now()->toDateString()))
                            ->pluck('p.key')
                            ->flip()
                            ->toArray()
                        : [];
                    $navCan = fn (string $key): bool => array_key_exists($key, $navPermissions);
                @endphp
                @include('layouts.partials.staff-nav', ['navCan' => $navCan])
            @endauth
        </nav>

        <div class="portal-header__actions">

            @include('layouts.partials.locale-switcher')

            @auth('staff')
            <form method="POST" action="{{ route('staff.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn--ghost btn--sm">
                    {{ __('ui.logout') }}
                </button>
            </form>
            @endauth
        </div>
    </div>
</header>

<div class="portal-body">
    @if(session('success') || session('error'))
        <div class="flash-region">
            @if(session('success'))
                <div class="alert alert--success" role="alert">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert--danger" role="alert">{{ session('error') }}</div>
            @endif
        </div>
    @endif

    <main id="main-content" class="portal-main" tabindex="-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>
</div>

@include('layouts.partials.footer')

@include('layouts.partials.confirm-dialog')

</body>
</html>
