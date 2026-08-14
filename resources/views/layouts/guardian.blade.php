<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Parent / Student Portal') — GCV DATA</title>
</head>
<body>
    <header>
        <strong>GCV DATA — Parent / Student Portal</strong>
    </header>

    <main>
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    {{--
        F09: Minimal layout establishing topology, not final UI design.
        The account belongs to a parent or authorized guardian, never to a student.
        Design tokens, branding, Tailwind, and full RTL/LTR support are
        deferred to the F20–F22 localization and branding phases.
        Login pages and Guardian Portal UI are deferred to F10 and later.
    --}}
</body>
</html>
