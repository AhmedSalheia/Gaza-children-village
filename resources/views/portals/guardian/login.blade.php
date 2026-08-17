@extends('layouts.guardian')

@section('title', __('auth.sign_in', [], null, 'Sign In'))

@section('content')
<div class="auth-layout">
    <section class="auth-hero" aria-hidden="true">
        <h2 class="auth-hero__title">{{ __('auth.guardian_hero_title', [], null, 'Follow your child\'s journey') }}</h2>
        <span class="auth-hero__accent"></span>
        <p class="auth-hero__body">{{ __('auth.guardian_hero_body', [], null, 'Results, attendance, and official documents for your children — securely, in one place.') }}</p>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <h1 class="auth-card__title">{{ __('auth.guardian_portal') }}</h1>
            <p class="auth-card__subtitle">{{ __('auth.sign_in_subtitle', [], null, 'Sign in to continue') }}</p>

            {{-- Generic error — never reveals account existence, password correctness,
                 or account lifecycle state. --}}
            @error('credentials')
                <div class="alert alert--danger" role="alert" id="login-error">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('guardian.login') }}" autocomplete="off" novalidate>
                @csrf

                <div class="form-group">
                    {{-- Field is labeled generically. Do NOT label it "National ID" —
                         the opaque login_identifier is not a stored civil-identity fact. --}}
                    <label for="login_identifier" class="form-label">{{ __('auth.login_identifier', [], null, 'Login ID') }}</label>
                    <input
                        type="text"
                        id="login_identifier"
                        name="login_identifier"
                        class="form-control @error('credentials') form-control--error @enderror"
                        value="{{ old('login_identifier') }}"
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

            <p class="auth-meta">{{ __('auth.guardian_help', [], null, 'Need access? Contact your school administration.') }}</p>
        </div>
    </section>
</div>
@endsection
