@extends('layouts.admin')

@section('title', __('auth.sign_in', [], null, 'Sign In'))

@section('content')
<div class="auth-layout">
    <section class="auth-hero" aria-hidden="true">
        <h2 class="auth-hero__title">{{ __('auth.admin_hero_title', [], null, 'One place for every school record') }}</h2>
        <span class="auth-hero__accent"></span>
        <p class="auth-hero__body">{{ __('auth.admin_hero_body', [], null, 'Manage institutions, staff, students, and academic records across the whole organization.') }}</p>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <h1 class="auth-card__title">{{ __('auth.admin_portal') }}</h1>
            <p class="auth-card__subtitle">{{ __('auth.sign_in_subtitle', [], null, 'Sign in to continue') }}</p>

            {{-- Generic error — never reveals whether the account exists, the password
                 was wrong, or the account is in a non-active state. --}}
            @error('credentials')
                <div class="alert alert--danger" role="alert" id="login-error">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('admin.login') }}" autocomplete="off" novalidate>
                @csrf

                <div class="form-group">
                    <label for="username" class="form-label">{{ __('auth.username', [], null, 'Username') }}</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control @error('credentials') form-control--error @enderror"
                        value="{{ old('username') }}"
                        autocomplete="username"
                        required
                        autofocus
                        aria-describedby="{{ $errors->has('credentials') ? 'login-error' : '' }}"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">{{ __('auth.password', [], null, 'Password') }}</label>
                    {{-- value is intentionally omitted — passwords must never be reflected --}}
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('credentials') form-control--error @enderror"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--full">
                    {{ __('auth.sign_in', [], null, 'Sign In') }}
                </button>
            </form>

            <p class="auth-meta">{{ __('auth.restricted_access', [], null, 'Access is restricted to authorized personnel.') }}</p>
        </div>
    </section>
</div>
@endsection
