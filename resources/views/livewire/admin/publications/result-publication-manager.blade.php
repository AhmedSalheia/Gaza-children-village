{{-- Livewire: App\Livewire\Admin\Publications\ResultPublicationManager --}}

<div>

    {{-- Page header --}}
    <div class="page-header">

        <div>
            <h1 class="page-title">
                {{ __('publications.publish_results_title') }}
            </h1>

            <p class="page-subtitle">
                {{ __('publications.publish_results_subtitle') }}
            </p>
        </div>

    </div>


    {{-- Flash message --}}
    @include('livewire.admin._partials.flash-message', [
        'message' => $flashMessage,
        'type' => $flashType
    ])


    {{-- Semester selector --}}
    <div class="filters-bar">

        <div>

            <label class="form-label">
                {{ __('publications.semester') }}
            </label>

            <select
                wire:model.live="semesterId"
                class="form-control form-select"
                style="max-inline-size:320px">

                <option value="0">
                    {{ __('publications.select_semester') }}
                </option>

                @foreach($openSemesters as $sem)

                    <option value="{{ $sem->id }}">
                        {{ $sem->institution_name }} —
                        {{ $sem->semester_name }}
                    </option>

                @endforeach

            </select>

        </div>

    </div>


    @if($semesterId > 0)

        <div class="form-grid form-grid--two">


            {{-- Publish group results --}}
            <div class="form-section">

                <h2 class="section-title">
                    {{ __('publications.publish_group_results') }}
                </h2>


                <div>

                    <label class="form-label">
                        {{ __('publications.class_group') }}
                    </label>

                    <select
                        wire:model.live="classGroupId"
                        class="form-control form-select">

                        <option value="0">
                            {{ __('publications.select_group') }}
                        </option>

                        @foreach($classGroups as $cg)

                            <option value="{{ $cg->id }}">
                                {{ $cg->name_ar }}
                            </option>

                        @endforeach

                    </select>

                </div>


                @if($classGroupId > 0 && $readiness)

                    <div class="alert
                        {{ $readiness->ready
                            ? 'alert--success'
                            : 'alert--warning' }}"
                        style="margin-top:16px">

                        <div style="font-weight:600">
                            {{ __('publications.sheets_status') }}
                        </div>

                        <div>
                            {{ __('publications.approved_of_total', [
                                'approved' => $readiness->approved,
                                'total' => $readiness->total
                            ]) }}
                        </div>

                        @if($readiness->outstanding > 0)

                            <div
                                style="
                                    font-size:var(--text-sm);
                                    margin-top:4px;
                                ">

                                {{ __('publications.outstanding_sheets', [
                                    'count' => $readiness->outstanding
                                ]) }}

                            </div>

                        @endif

                    </div>


                    @if($canPublish)

                        <div class="form-actions">

                            <button
                                wire:click="publish"
                                wire:confirm="{{ __('publications.publish_confirm') }}"
                                class="btn btn--primary btn--sm"
                                @unless($readiness->ready) disabled @endunless>

                                {{ __('publications.publish_results') }}

                            </button>

                        </div>

                    @endif

                @endif

            </div>


            {{-- Publication history --}}
            <div class="form-section">

                <h2 class="section-title">
                    {{ __('publications.version_history') }}
                </h2>


                @if($classGroupId > 0)

                    <div class="data-table-wrapper">

                        <table class="data-table">

                            <thead>

                                <tr>

                                    <th>
                                        {{ __('publications.version_history') }}
                                    </th>

                                    <th>
                                        {{ __('ui.status') }}
                                    </th>

                                    <th>
                                        {{ __('publications.revoke') }}
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse($publications as $pub)

                                    <tr
                                        style="
                                            {{ $pub->status === 'revoked'
                                                ? 'opacity:.55;'
                                                : '' }}
                                        ">

                                        {{-- Version --}}
                                        <td>

                                            <span class="badge
                                                badge--{{
                                                    $pub->status === 'published'
                                                        && ! $pub->superseded_by_id
                                                            ? 'active'
                                                            : (
                                                                $pub->status === 'revoked'
                                                                    ? 'closed'
                                                                    : 'pending'
                                                            )
                                                }}">

                                                {{ __('publications.version_label', [
                                                    'version' => $pub->version
                                                ]) }}

                                            </span>

                                            @if($pub->superseded_by_id)

                                                <span
                                                    style="
                                                        font-size:var(--text-sm);
                                                        color:var(--text-secondary);
                                                        margin-inline-start:6px;
                                                    ">

                                                    {{ __('publications.superseded') }}

                                                </span>

                                            @endif

                                            @if($pub->status === 'revoked')

                                                <span
                                                    style="
                                                        font-size:var(--text-sm);
                                                        color:var(--text-secondary);
                                                        margin-inline-start:6px;
                                                    ">

                                                    {{ __('publications.revoked') }}

                                                </span>

                                            @endif

                                        </td>


                                        {{-- Published date --}}
                                        <td>

                                            <span
                                                style="
                                                    font-size:var(--text-sm);
                                                    color:var(--text-secondary);
                                                ">

                                                {{ \Carbon\Carbon::parse(
                                                    $pub->published_at
                                                )->format('Y-m-d H:i') }}

                                            </span>

                                        </td>


                                        {{-- Action --}}
                                        <td>

                                            @if($pub->status === 'published' && $canRevoke)

                                                <button
                                                    wire:click="startRevoke({{ $pub->id }})"
                                                    class="btn btn--danger btn--sm">

                                                    {{ __('publications.revoke') }}

                                                </button>

                                            @endif

                                        </td>

                                    </tr>


                                    {{-- Revoke reason --}}
                                    @if($pub->status === 'revoked')

                                        <tr>

                                            <td colspan="3">

                                                <div
                                                    style="
                                                        font-size:var(--text-sm);
                                                        color:var(--danger, #dc2626);
                                                    ">

                                                    <strong>
                                                        {{ __('publications.reason_label') }}
                                                    </strong>

                                                    {{ $pub->revoke_reason }}

                                                </div>

                                            </td>

                                        </tr>

                                    @endif


                                @empty

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="empty-state">

                                            {{ __('publications.no_versions') }}

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="empty-state">

                        {{ __('publications.select_group') }}

                    </div>

                @endif

            </div>

        </div>

    @endif


    {{-- Revoke modal --}}
    @if($revokingId > 0)

        <div class="modal-backdrop-light"></div>

        <div
            class="form-section"
            style="
                position:fixed;
                inset-inline-start:50%;
                top:50%;
                transform:translate(-50%, -50%);
                width:min(460px, calc(100vw - 32px));
                z-index:1055;
                box-shadow:0 20px 50px rgba(0,0,0,.15);
            ">

            <h2 class="section-title">
                {{ __('publications.revoke_publication_title') }}
            </h2>


            <p
                style="
                    color:var(--text-secondary);
                    font-size:var(--text-sm);
                    margin-bottom:16px;
                ">

                {{ __('publications.revoke_intro') }}

            </p>


            <div>

                <label class="form-label">
                    {{ __('publications.reason_label') }}
                </label>

                <textarea
                    wire:model="revokeReason"
                    class="form-control"
                    rows="4"
                    placeholder="{{ __('publications.revoke_reason_placeholder') }}">
                </textarea>

                @error('revokeReason')

                    <p class="form-error">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <div class="form-actions">

                <button
                    wire:click="confirmRevoke"
                    class="btn btn--danger btn--sm">

                    {{ __('publications.confirm_revoke') }}

                </button>


                <button
                    wire:click="cancelRevoke"
                    class="btn btn--outline btn--sm">

                    {{ __('publications.back') }}

                </button>

            </div>

        </div>

    @endif


    @include('livewire.admin._partials.page-styles')

</div>
