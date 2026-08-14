<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.admin_portal')) — GCV DATA</title>

    {{-- F22: Design tokens + self-hosted fonts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="gcv-layout gcv-layout--admin">

{{-- Skip navigation for WCAG 2.4.1 --}}
<a href="#main-content" class="skip-link">{{ __('ui.skip_to_content', [], null, 'Skip to main content') }}</a>

{{-- F23: Shared portal header --}}
<header class="portal-header" role="banner">
    <div class="portal-header__inner">
        <a href="{{ url('/admin') }}" class="portal-header__brand" aria-label="GCV DATA — {{ __('auth.admin_portal') }}">
            <img
                src="{{ asset('brand/gcv-logo-dark.png') }}"
                alt="GCV DATA"
                class="portal-header__logo"
                width="120"
                height="40"
            >
        </a>

        <nav class="portal-header__nav" role="navigation" aria-label="{{ __('auth.admin_portal') }}">
            @auth('admin')
                @include('layouts.partials.admin-nav')
            @endauth
        </nav>

        <div class="portal-header__actions">
            {{-- F20: Locale switcher --}}
            @include('layouts.partials.locale-switcher')

            @auth('admin')
            <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn--ghost btn--sm">
                    {{ __('ui.logout') }}
                </button>
            </form>
            @endauth
        </div>
    </div>
</header>

{{-- Main content --}}
<div class="portal-body">
    @if(session('success'))
        <div class="alert alert--success" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert--danger" role="alert">{{ session('error') }}</div>
    @endif

    <main id="main-content" class="portal-main" tabindex="-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>
</div>

{{-- F23: Confirmation dialog (global) --}}
@include('layouts.partials.confirm-dialog')

</body>
</html>
