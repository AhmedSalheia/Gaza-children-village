@php /** @var \App\Livewire\Staff\Enrollments\EnrollmentManagement $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;margin:0">{{ __('ui.enrollments', [], null, 'Enrollments') }}</h1>
        <button wire:click="$set('showCreateForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.create_enrollment', [], null, 'Create Enrollment') }}
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert--success">{{ session('success') }}</div>
    @endif

    @error('createEnrollment') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('changePlacement') <div class="alert alert--danger">{{ $message }}</div> @enderror

    {{-- Create form --}}
    @if($showCreateForm)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.create_draft_enrollment', [], null, 'Create Draft Enrollment') }}</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-4)">
            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.student_profile_id', [], null, 'Student Profile ID') }}</label>
                <input type="number" wire:model="createStudentId" class="form-control @error('createStudentId') form-control--error @enderror" min="1">
                @error('createStudentId') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.class_group', [], null, 'Class Group') }}</label>
                <select wire:model="createClassGroupId" class="form-control form-select @error('createClassGroupId') form-control--error @enderror">
                    <option value="0">— {{ __('ui.select', [], null, 'Select') }} —</option>
                    @foreach($classGroups as $cg)
                    <option value="{{ $cg->id }}">{{ $cg->level_name }} — {{ $cg->name_ar }}</option>
                    @endforeach
                </select>
                @error('createClassGroupId') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.enrolled_on', [], null, 'Enrolled On') }}</label>
                <input type="date" wire:model="createEnrolledOn" class="form-control @error('createEnrolledOn') form-control--error @enderror">
                @error('createEnrolledOn') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">{{ __('ui.notes', [], null, 'Notes') }}</label>
            <input type="text" wire:model="createNotes" class="form-control">
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="createDraftEnrollment" class="btn btn--primary">{{ __('ui.create', [], null, 'Create') }}</button>
            <button wire:click="$set('showCreateForm', false)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- Change placement form --}}
    @if($changingEnrollmentId)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.change_placement', [], null, 'Change Class Placement') }}</h2>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.new_class_group', [], null, 'New Class Group') }}</label>
            <select wire:model="newClassGroupId" class="form-control form-select @error('newClassGroupId') form-control--error @enderror">
                <option value="0">— {{ __('ui.select', [], null, 'Select') }} —</option>
                @foreach($classGroups as $cg)
                <option value="{{ $cg->id }}">{{ $cg->level_name }} — {{ $cg->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="changePlacement" class="btn btn--primary">{{ __('ui.save', [], null, 'Save') }}</button>
            <button wire:click="$set('changingEnrollmentId', null)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- Withdraw/suspend notes --}}
    @if($withdrawingEnrollmentId || $suspendingEnrollmentId)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
            {{ $withdrawingEnrollmentId ? __('ui.withdraw_enrollment', [], null, 'Withdraw Enrollment') : __('ui.suspend_enrollment', [], null, 'Suspend Enrollment') }}
        </h2>
        <div class="form-group">
            <label class="form-label">{{ __('ui.notes', [], null, 'Notes') }}</label>
            <input type="text" wire:model="actionNotes" class="form-control">
        </div>
        <div style="display:flex;gap:var(--space-3)">
            @if($withdrawingEnrollmentId)
            <button wire:click="withdraw({{ $withdrawingEnrollmentId }})" class="btn btn--danger">{{ __('ui.confirm_withdraw', [], null, 'Confirm Withdrawal') }}</button>
            <button wire:click="$set('withdrawingEnrollmentId', null)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            @endif
            @if($suspendingEnrollmentId)
            <button wire:click="suspend({{ $suspendingEnrollmentId }})" class="btn btn--danger">{{ __('ui.confirm_suspend', [], null, 'Confirm Suspension') }}</button>
            <button wire:click="$set('suspendingEnrollmentId', null)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            @endif
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div style="display:grid;grid-template-columns:1fr auto;gap:var(--space-3);margin-block-end:var(--space-4);align-items:end">
        <div class="form-group" style="margin:0">
            <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                placeholder="{{ __('ui.search', [], null, 'Search by name or code…') }}">
        </div>
        <div class="form-group" style="margin:0">
            <select wire:model.live="statusFilter" class="form-control form-select">
                <option value="">{{ __('ui.all_statuses', [], null, 'All Statuses') }}</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('ui.student', [], null, 'Student') }}</th>
                <th>{{ __('ui.class_group', [], null, 'Class') }}</th>
                <th>{{ __('ui.level', [], null, 'Level') }}</th>
                <th>{{ __('ui.status', [], null, 'Status') }}</th>
                <th>{{ __('ui.enrolled_on', [], null, 'Enrolled') }}</th>
                <th>{{ __('ui.actions', [], null, 'Actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse($enrollments as $e)
                <tr>
                    <td>
                        <a href="{{ route('staff.students.detail', ['studentProfileId' => $e->student_id]) }}" class="link" wire:navigate>{{ $e->student_name }}</a>
                        <div style="font-size:var(--text-xs);color:var(--text-secondary)">{{ $e->student_code }}</div>
                    </td>
                    <td>{{ $e->class_group_name }}</td>
                    <td>{{ $e->level_name }}</td>
                    <td><span class="badge badge--{{ match($e->enrollment_status) {'active'=>'active','draft'=>'draft','suspended'=>'pending',default=>'closed'} }}">{{ $e->enrollment_status }}</span></td>
                    <td>{{ $e->enrolled_on }}</td>
                    <td style="white-space:nowrap">
                        @if($e->enrollment_status === 'draft')
                            <button wire:click="startChangePlacement({{ $e->id }})" class="btn btn--outline btn--sm">{{ __('ui.change_placement_short', [], null, 'Placement') }}</button>
                            <button wire:click="activate({{ $e->id }})" class="btn btn--secondary btn--sm">{{ __('ui.activate', [], null, 'Activate') }}</button>
                            <button wire:click="$set('withdrawingEnrollmentId', {{ $e->id }})" class="btn btn--ghost btn--sm">{{ __('ui.withdraw', [], null, 'Withdraw') }}</button>
                        @elseif($e->enrollment_status === 'active')
                            @if($canTransfer)
                            <a href="{{ route('staff.enrollments.transfer', ['studentProfileId' => $e->student_id]) }}" class="btn btn--outline btn--sm" wire:navigate>{{ __('ui.transfer', [], null, 'Transfer') }}</a>
                            @endif
                            <button wire:click="$set('suspendingEnrollmentId', {{ $e->id }})" class="btn btn--ghost btn--sm">{{ __('ui.suspend', [], null, 'Suspend') }}</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary);padding:var(--space-8);font-style:italic">{{ __('ui.no_enrollments', [], null, 'No enrollments found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $enrollments->links() }}
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>
