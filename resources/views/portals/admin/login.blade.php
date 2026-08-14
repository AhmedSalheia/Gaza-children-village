@extends('layouts.admin')

@section('title', 'Sign In')

@section('content')
<main>
    <h1>Admin Portal — Sign In</h1>

    {{-- Generic error — never reveals whether the account exists, the password
         was wrong, or the account is in a non-active state. --}}
    @error('credentials')
        <p role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('admin.login') }}" autocomplete="off" novalidate>
        @csrf

        <div>
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username') }}"
                autocomplete="username"
                required
                autofocus
                aria-describedby="{{ $errors->has('credentials') ? 'login-error' : '' }}"
            >
        </div>

        <div>
            <label for="password">Password</label>
            {{-- value is intentionally omitted — passwords must never be reflected --}}
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </div>

        <div>
            <button type="submit">Sign In</button>
        </div>
    </form>

    {{--
        F10: Minimal login form establishing topology, not final UI design.
        Password reset and account-management links are deferred to F11.
        Branding, Tailwind, and RTL/LTR design system are deferred to F20–F22.
    --}}
</main>
@endsection
