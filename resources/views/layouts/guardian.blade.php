<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.guardian_portal')) — GCV DATA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="gcv-layout gcv-layout--guardian">

<a href="#main-content" class="skip-link">{{ __('ui.skip_to_content', [], null, 'Skip to main content') }}</a>

<header class="portal-header" role="banner">
    <div class="portal-header__inner">
        <a href="{{ route('guardian.dashboard') }}" class="portal-header__brand brand-mark" aria-label="GCV DATA — {{ __('auth.guardian_portal') }}">
            <span class="brand-mark__glyph" aria-hidden="true">GCV</span>
            <span class="brand-mark__text">
                <span class="brand-mark__name">GCV DATA</span>
                <span class="brand-mark__tagline">{{ __('ui.org_name', [], null, 'Gaza Community Volunteers') }}</span>
            </span>
        </a>

        <div class="portal-header__actions">
            {{-- Notification bell (in-app notifications) --}}
            @auth('guardian')
            <livewire:notifications.notification-bell portal="guardian" />
            @endauth

            @include('layouts.partials.locale-switcher')

            @auth('guardian')
            <form method="POST" action="{{ route('guardian.logout') }}" class="d-inline">
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

<footer class="portal-footer">
    <div class="portal-footer__inner">
        <span>GCV DATA — {{ __('ui.org_name', [], null, 'Gaza Community Volunteers') }}</span>
        <span>{{ __('ui.footer_rights', ['year' => now()->year], null, '© :year All rights reserved') }}</span>
    </div>
</footer>

@include('layouts.partials.confirm-dialog')

</body>
</html>
