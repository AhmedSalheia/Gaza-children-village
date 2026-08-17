@php /** @var \App\Livewire\Staff\Marks\MarksVerificationQueue $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('marks.review_queue') }}</h1>
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Return dialog --}}
    @if($returningId > 0)
        <div class="card" style="margin-block-end:var(--space-4);border-color:var(--color-warning)">
            <h3 style="margin-block-end:var(--space-2)">{{ __('marks.return_reason') }}</h3>
            <textarea wire:model="returnReason" class="form-control" rows="3" placeholder="{{ __('marks.return_reason_placeholder') }}"></textarea>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-2)">
                <button wire:click="confirmReturn" class="btn btn--danger btn--sm">{{ __('marks.confirm_return') }}</button>
                <button wire:click="cancelReturn" class="btn btn--outline btn--sm">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    {{-- Submitted sheets (secretary queue) --}}
    <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-3)">{{ __('marks.awaiting_verification') }}</h2>
    <div class="data-table-wrapper" style="margin-block-end:var(--space-6)">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('marks.class') }}</th>
                    <th>{{ __('ui.subject') }}</th>
                    <th>{{ __('assignments.teacher') }}</th>
                    <th>{{ __('ui.submitted') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submittedSheets as $sheet)
                    <tr>
                        <td dir="rtl">{{ $sheet->class_name }}</td>
                        <td dir="rtl">{{ $sheet->subject_name }}</td>
                        <td dir="rtl">{{ $sheet->teacher_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($sheet->submitted_at)->format('d/m/Y H:i') }}</td>
                        <td style="display:flex;gap:var(--space-1)">
                            <button wire:click="verify({{ $sheet->id }})" class="btn btn--primary btn--sm">{{ __('marks.verify') }}</button>
                            @if($canReturn)
                                <button wire:click="startReturn({{ $sheet->id }})" class="btn btn--outline btn--sm">{{ __('marks.return') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">{{ __('marks.no_sheets_verification') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Verified sheets (principal queue) --}}
    @if($canApprove)
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-3)">{{ __('marks.awaiting_approval') }}</h2>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('marks.class') }}</th>
                        <th>{{ __('ui.subject') }}</th>
                        <th>{{ __('assignments.teacher') }}</th>
                        <th>{{ __('ui.verified') }}</th>
                        <th>{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($verifiedSheets as $sheet)
                        <tr>
                            <td dir="rtl">{{ $sheet->class_name }}</td>
                            <td dir="rtl">{{ $sheet->subject_name }}</td>
                            <td dir="rtl">{{ $sheet->teacher_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($sheet->verified_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <button wire:click="approve({{ $sheet->id }})" class="btn btn--primary btn--sm">{{ __('marks.approve') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">{{ __('marks.no_sheets_approval') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
