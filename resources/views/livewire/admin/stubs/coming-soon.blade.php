@php /** @var \App\Livewire\Admin\Stubs\ComingSoonPage $this */ @endphp

<div style="display:flex;align-items:center;justify-content:center;min-block-size:60vh">
    <div style="text-align:center;max-inline-size:480px;padding:var(--space-8)">
        {{-- GCV teal circle icon --}}
        <div style="
            width:5rem;height:5rem;border-radius:50%;
            background:var(--teal-50);border:2px solid var(--teal-200);
            display:flex;align-items:center;justify-content:center;
            margin:0 auto var(--space-6);
            font-size:2rem;
        ">🔒</div>

        <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin-block-end:var(--space-3)">
            {{ __('ui.coming_in_next_release', [], null, 'Available in the Full Admin Portal Release') }}
        </h1>

        <p style="color:var(--text-secondary);margin-block-end:var(--space-4);line-height:var(--leading-relaxed)">
            {{ __('ui.coming_soon_body', [], null, 'Staff management, account administration, role configuration, and full audit logs are implemented in the Full Admin Portal release. The Student Registry and Enrolment features are available now from the navigation above.') }}
        </p>

        <div style="display:flex;gap:var(--space-3);justify-content:center">
            <a href="{{ route('admin.dashboard') }}" class="btn btn--primary" wire:navigate>
                {{ __('ui.go_to_dashboard', [], null, 'Go to Dashboard') }}
            </a>
            <a href="{{ route('admin.students.index') }}" class="btn btn--outline" wire:navigate>
                {{ __('ui.view_students', [], null, 'View Students') }}
            </a>
        </div>

        <p style="margin-block-start:var(--space-6);font-size:var(--text-sm);color:var(--text-secondary)">
            {{ __('ui.sr7_release', [], null, 'SR-7 Admin Portal — Student Registry Module') }}
        </p>
    </div>
</div>
