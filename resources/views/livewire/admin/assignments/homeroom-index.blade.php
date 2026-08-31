@php
    /** @var \App\Livewire\Admin\Assignments\HomeroomAssignments $this */
@endphp

<div>

    {{-- Page header --}}
    <div class="page-header">

        <h1 class="page-title">
            {{ __('assignments.homeroom_title') }}
        </h1>

        @if($canManage)
            <button
                wire:click="$set('showForm', true)"
                class="btn btn--primary btn--sm">
                + {{ __('assignments.new_assignment') }}
            </button>
        @endif

    </div>


    {{-- Flash message --}}
    @if($flashMessage)
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}">
            {{ $flashMessage }}
        </div>
    @endif


    {{-- Filters --}}
    <div class="filters-bar">

        <div>

            <label class="form-label">
                {{ __('ui.institution_semesters') }}
            </label>

            <select
                wire:model.live="instSemId"
                class="form-control form-select"
                style="max-inline-size:280px">

                <option value="0">
                    {{ __('assignments.select_semester') }}
                </option>

                @foreach($openSemesters as $sem)
                    <option value="{{ $sem->id }}">
                        {{ $sem->institution_name }} —
                        {{ $sem->semester_name }}
                        ({{ $sem->status }})
                    </option>
                @endforeach

            </select>

        </div>


        <label class="flex items-center gap-2 text-sm text-gray-700">

            <input
                type="checkbox"
                wire:model.live="showHistory"
                class="rounded">

            {{ __('assignments.show_history') }}

        </label>

    </div>


    {{-- Create form --}}
    @if($showForm && $canManage)

        <div class="form-section">

            <h2 class="section-title">
                {{ __('assignments.new_homeroom_assignment') }}
            </h2>


            @if($instSemId === 0)

                <p class="text-sm text-gray-500">
                    {{ __('assignments.select_semester_first') }}
                </p>

            @else

                <div class="form-grid">

                    {{-- Teacher --}}
                    <div>

                        <label class="form-label">
                            {{ __('assignments.staff_position_teacher') }}
                        </label>

                        <select
                            wire:model="formPositionId"
                            class="form-control form-select">

                            <option value="0">
                                {{ __('assignments.select_position') }}
                            </option>

                            @foreach($eligiblePositions as $pos)
                                <option value="{{ $pos->id }}">
                                    {{ $pos->staff_name }}
                                    ({{ $pos->position_definition }})
                                </option>
                            @endforeach

                        </select>

                        @error('formPositionId')
                            <p class="form-error">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Class --}}
                    <div>

                        <label class="form-label">
                            {{ __('ui.class_group') }}
                        </label>

                        <select
                            wire:model="formClassGroupId"
                            class="form-control form-select">

                            <option value="0">
                                {{ __('assignments.select_class') }}
                            </option>

                            @foreach($classGroups as $cg)
                                <option value="{{ $cg->id }}">
                                    {{ $cg->name_ar }}
                                    ({{ $cg->level_name }})
                                </option>
                            @endforeach

                        </select>

                        @error('formClassGroupId')
                            <p class="form-error">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Start date --}}
                    <div>

                        <label class="form-label">
                            {{ __('assignments.starts_on') }}
                        </label>

                        <input
                            type="date"
                            wire:model="formStartsOn"
                            class="form-control">

                        @error('formStartsOn')
                            <p class="form-error">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Co lead --}}
                    <div class="flex items-center gap-2">

                        <input
                            type="checkbox"
                            wire:model="formIsCoLead"
                            id="coLead"
                            class="rounded">

                        <label for="coLead" class="text-sm">
                            {{ __('assignments.co_lead_secondary') }}
                        </label>

                    </div>

                </div>


                {{-- Buttons --}}
                <div class="form-actions">

                    <button
                        wire:click="save"
                        class="btn btn--primary btn--sm">
                        {{ __('ui.save') }}
                    </button>

                    <button
                        wire:click="$set('showForm', false)"
                        class="btn btn--outline btn--sm">
                        {{ __('ui.cancel') }}
                    </button>

                </div>

            @endif

        </div>

    @endif


    {{-- End assignment --}}
    @if($endingId)

        <div class="form-section form-section--danger">

            <h2 class="section-title">
                {{ __('assignments.end_homeroom_assignment', ['id' => $endingId]) }}
            </h2>

            <p class="text-sm text-gray-500">
                {!! __('assignments.end_notice') !!}
            </p>


            <div class="form-grid">

                <div>

                    <label class="form-label">
                        {{ __('assignments.end_date') }}
                    </label>

                    <input
                        type="date"
                        wire:model="endDate"
                        class="form-control">

                    @error('endDate')
                        <p class="form-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div>

                    <label class="form-label">
                        {{ __('ui.reason') }}
                    </label>

                    <input
                        type="text"
                        wire:model="endReason"
                        placeholder="{{ __('assignments.reason_ending_placeholder') }}"
                        class="form-control">

                    @error('endReason')
                        <p class="form-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>


            <div class="form-actions">

                <button
                    wire:click="confirmEnd"
                    class="btn btn--danger btn--sm">
                    {{ __('assignments.confirm_end') }}
                </button>

                <button
                    wire:click="cancelEnd"
                    class="btn btn--outline btn--sm">
                    {{ __('ui.cancel') }}
                </button>

            </div>

        </div>

    @endif


    {{-- Replace assignment --}}
    @if($replacingId)

        <div class="form-section form-section--warning">

            <h2 class="section-title">
                {{ __('assignments.replace_homeroom_assignment', ['id' => $replacingId]) }}
            </h2>

            <p class="text-sm text-gray-500">
                {!! __('assignments.replace_homeroom_notice') !!}
            </p>


            <div class="form-grid">

                {{-- Replacement teacher --}}
                <div>

                    <label class="form-label">
                        {{ __('assignments.replacement_teacher') }}
                    </label>

                    <select
                        wire:model="replacePositionId"
                        class="form-control form-select">

                        <option value="0">
                            {{ __('assignments.select_new_position') }}
                        </option>

                        @foreach($eligiblePositions as $pos)
                            <option value="{{ $pos->id }}">
                                {{ $pos->staff_name }}
                                ({{ $pos->position_definition }})
                            </option>
                        @endforeach

                    </select>

                    @error('replacePositionId')
                        <p class="form-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Effective date --}}
                <div>

                    <label class="form-label">
                        {{ __('assignments.effective_date') }}
                    </label>

                    <input
                        type="date"
                        wire:model="replaceDate"
                        class="form-control">

                    @error('replaceDate')
                        <p class="form-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Reason --}}
                <div>

                    <label class="form-label">
                        {{ __('assignments.reason_replacement') }}
                    </label>

                    <input
                        type="text"
                        wire:model="replaceReason"
                        placeholder="{{ __('assignments.reason_replacement_homeroom_placeholder') }}"
                        class="form-control">

                    @error('replaceReason')
                        <p class="form-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>


            <div class="form-actions">

                <button
                    wire:click="confirmReplace"
                    class="btn btn--warning btn--sm">
                    {{ __('assignments.confirm_replace') }}
                </button>

                <button
                    wire:click="cancelReplace"
                    class="btn btn--outline btn--sm">
                    {{ __('ui.cancel') }}
                </button>

            </div>

        </div>

    @endif


    {{-- Table --}}
    @if($instSemId === 0)

        <div class="empty-state">
            {{ __('assignments.select_semester_to_view') }}
        </div>

    @elseif(
        $assignments instanceof \Illuminate\Support\Collection
            ? $assignments->isEmpty()
            : $assignments->total() === 0
    )

        <div class="empty-state">
            {{ __('assignments.no_assignments') }}
        </div>

    @else

        <div class="data-table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            {{ __('ui.class_group') }}
                        </th>

                        <th>
                            {{ __('ui.level') }}
                        </th>

                        <th>
                            {{ __('assignments.homeroom_teacher') }}
                        </th>

                        <th>
                            {{ __('assignments.role') }}
                        </th>

                        <th>
                            {{ __('assignments.from') }}
                        </th>

                        <th>
                            {{ __('ui.status') }}
                        </th>

                        @if($canManage)
                            <th></th>
                        @endif

                    </tr>

                </thead>


                <tbody>

                    @foreach($assignments as $row)

                        <tr>

                            <td>
                                <div style="font-weight:600">
                                    {{ $row->class_group_name }}
                                </div>
                            </td>


                            <td>
                                <span style="color:var(--text-secondary)">
                                    {{ $row->level_name }}
                                </span>
                            </td>


                            <td>

                                <div style="font-weight:600">
                                    {{ $row->staff_name ?? '—' }}
                                </div>

                            </td>


                            <td>

                                <span class="badge badge--{{ $row->is_co_lead ? 'pending' : 'active' }}">
                                    {{ $row->is_co_lead
                                        ? __('assignments.co_lead')
                                        : __('assignments.lead') }}
                                </span>

                            </td>


                            <td>
                                <span style="font-size:var(--text-sm);color:var(--text-secondary)">
                                    {{ $row->starts_on }}
                                </span>
                            </td>


                            <td>

                                <span class="badge badge--{{ match($row->status) {

                                    'active' => 'active',

                                    'superseded' => 'pending',

                                    default => 'closed'

                                } }}">

                                    {{ __('ui.'.$row->status) }}

                                </span>

                            </td>


                            @if($canManage)

                                <td>

                                    @if($row->status === 'active')

                                        <div class="table-actions">

                                            <button
                                                wire:click="startEnd({{ $row->id }})"
                                                class="btn btn--danger btn--sm">
                                                {{ __('ui.end') }}
                                            </button>

                                            <button
                                                wire:click="startReplace({{ $row->id }})"
                                                class="btn btn--outline btn--sm">
                                                {{ __('assignments.replace') }}
                                            </button>

                                        </div>

                                    @endif

                                </td>

                            @endif

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if(method_exists($assignments, 'links'))

            <div class="pagination">
                {{ $assignments->links() }}
            </div>

        @endif

    @endif

</div>

@include('livewire.admin._partials.page-styles')
