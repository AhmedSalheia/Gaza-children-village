@extends('layouts.staff')

@section('title', 'Sign In')

@section('content')
<main>
    <h1>Staff Portal — Sign In</h1>

    {{-- Generic error — never reveals account existence, password correctness,
         or account lifecycle state. --}}
    @error('credentials')
        <p role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('staff.login') }}" autocomplete="off" novalidate>
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
        F10: Minimal login form. Password reset deferred to F11.
        Branding and RTL/LTR deferred to F20–F22.
        Staff authentication grants no institution data access by itself.
    --}}
</main>
@endsection
