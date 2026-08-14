@extends('layouts.guardian')

@section('title', 'Sign In')

@section('content')
<main>
    <h1>Parent/Student Portal — Sign In</h1>

    {{-- Generic error — never reveals account existence, password correctness,
         or account lifecycle state. --}}
    @error('credentials')
        <p role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('guardian.login') }}" autocomplete="off" novalidate>
        @csrf

        <div>
            {{-- Field is labeled generically. Do NOT label it "National ID" —
                 the opaque login_identifier is not a stored civil-identity fact.
                 Guardian national-ID resolution is deferred to F11/F13. --}}
            <label for="login_identifier">Login ID</label>
            <input
                type="text"
                id="login_identifier"
                name="login_identifier"
                value="{{ old('login_identifier') }}"
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
        F10: Minimal login form. Guardian national-ID resolution deferred to F11/F13.
        No student access until a verified guardian-student relationship exists (F15).
        Branding and RTL/LTR deferred to F20–F22.
    --}}
</main>
@endsection
