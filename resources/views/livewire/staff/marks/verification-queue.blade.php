@php /** @var \App\Livewire\Staff\Marks\MarksVerificationQueue $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Marks Review Queue</h1>
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
            <h3 style="margin-block-end:var(--space-2)">Return Reason</h3>
            <textarea wire:model="returnReason" class="form-control" rows="3" placeholder="Explain what the teacher needs to fix…"></textarea>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-2)">
                <button wire:click="confirmReturn" class="btn btn--danger btn--sm">Confirm Return</button>
                <button wire:click="cancelReturn" class="btn btn--outline btn--sm">Cancel</button>
            </div>
        </div>
    @endif

    {{-- Submitted sheets (secretary queue) --}}
    <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-3)">Awaiting Verification</h2>
    <div class="data-table-wrapper" style="margin-block-end:var(--space-6)">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Submitted</th>
                    <th>Actions</th>
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
                            <button wire:click="verify({{ $sheet->id }})" class="btn btn--primary btn--sm">Verify</button>
                            @if($canReturn)
                                <button wire:click="startReturn({{ $sheet->id }})" class="btn btn--outline btn--sm">Return</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">No sheets awaiting verification.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Verified sheets (principal queue) --}}
    @if($canApprove)
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-3)">Awaiting Approval</h2>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Verified</th>
                        <th>Actions</th>
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
                                <button wire:click="approve({{ $sheet->id }})" class="btn btn--primary btn--sm">Approve</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No sheets awaiting approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
