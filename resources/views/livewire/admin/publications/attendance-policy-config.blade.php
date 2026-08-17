{{-- Livewire: App\Livewire\Admin\Publications\AttendancePublicationPolicyConfig --}}
<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('publications.attendance_config_title') }}</h1>
        <p class="page-subtitle">{{ __('publications.attendance_config_subtitle') }}</p>
    </div>

    @include('livewire.admin._partials.flash', ['message' => $flashMessage, 'type' => $flashType])

    {{-- Semester selector --}}
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label">{{ __('publications.semester') }}</label>
            <select wire:model.live="semesterId" class="form-select">
                <option value="0">{{ __('publications.select_semester') }}</option>
                @foreach($openSemesters as $sem)
                    <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($semesterId > 0)
        <div class="row">
            {{-- Policy form --}}
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header">{{ __('publications.policy_settings') }}</div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" wire:model.live="policyEnabled" id="policyEnabled">
                            <label class="form-check-label" for="policyEnabled">
                                <strong>{{ __('publications.enable_attendance_publishing') }}</strong>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('publications.detail_level') }}</label>
                            <select wire:model.live="detailLevel" class="form-select">
                                <option value="summary_only">{{ __('publications.detail_summary_only') }}</option>
                                <option value="daily_status">{{ __('publications.detail_daily_status') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('publications.publish_delay_days') }}</label>
                            <input type="number" wire:model.live="publishDelayDays" class="form-control"
                                min="0" max="30" placeholder="{{ __('publications.delay_placeholder') }}">
                            @error('publishDelayDays') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" wire:model.live="showReason" id="showReason">
                            <label class="form-check-label" for="showReason">{{ __('publications.show_absence_reason') }}</label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" wire:model.live="showArrivalDeparture" id="showArrDep">
                            <label class="form-check-label" for="showArrDep">{{ __('publications.show_arrival_departure') }}</label>
                        </div>

                        @if($canPublish)
                            <button wire:click="savePolicy" class="btn btn-primary w-100">{{ __('publications.save_settings') }}</button>
                        @endif
                    </div>
                </div>

                {{-- Publish snapshot --}}
                @if($classGroupId > 0 && $policy?->enabled)
                    <div class="card">
                        <div class="card-header">{{ __('publications.publish_snapshot') }}</div>
                        <div class="card-body">
                            @if($canPublish)
                                <button wire:click="publishSnapshot"
                                    wire:confirm="{{ __('publications.publish_snapshot_confirm') }}"
                                    class="btn btn-success w-100">
                                    {{ __('publications.publish_attendance_snapshot') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Class group + snapshot history --}}
            <div class="col-md-7">
                <div class="card mb-3">
                    <div class="card-header">{{ __('publications.class_group') }}</div>
                    <div class="card-body">
                        <select wire:model.live="classGroupId" class="form-select">
                            <option value="0">{{ __('publications.select_group') }}</option>
                            @foreach($classGroups as $cg)
                                <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($classGroupId > 0)
                    <div class="card">
                        <div class="card-header">{{ __('publications.snapshot_history') }}</div>
                        <div class="card-body p-0">
                            @forelse($snapshots as $snap)
                                <div class="border-bottom p-3 {{ $snap->status === 'revoked' ? 'opacity-50' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge {{ $snap->status === 'published' && !$snap->superseded_by_id ? 'bg-success' : ($snap->status === 'revoked' ? 'bg-danger' : 'bg-secondary') }} me-2">
                                                {{ __('publications.version_label', ['version' => $snap->version]) }}
                                                @if($snap->superseded_by_id) ({{ __('publications.superseded') }}) @endif
                                                @if($snap->status === 'revoked') ({{ __('publications.revoked') }}) @endif
                                            </span>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($snap->published_at)->format('Y-m-d') }}
                                                @if($snap->period_from)
                                                    | {{ $snap->period_from }} → {{ $snap->period_to }}
                                                @endif
                                            </small>
                                        </div>
                                        @if($snap->status === 'published' && $canPublish)
                                            <button wire:click="startRevokeSnapshot({{ $snap->id }})"
                                                class="btn btn-sm btn-outline-danger">{{ __('publications.revoke') }}</button>
                                        @endif
                                    </div>
                                    @if($snap->status === 'revoked')
                                        <div class="mt-1 small text-danger">{{ __('publications.reason_label') }} {{ $snap->revoke_reason }}</div>
                                    @endif
                                </div>
                            @empty
                                <div class="p-3 text-muted">{{ __('publications.no_snapshots') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Revoke snapshot modal --}}
    @if($revokingSnapshotId > 0)
        <div class="modal-backdrop-light"></div>
        <div class="card position-fixed top-50 start-50 translate-middle shadow-lg" style="width:460px;z-index:1055">
            <div class="card-header text-danger">{{ __('publications.revoke_snapshot_title') }}</div>
            <div class="card-body">
                <p>{{ __('publications.revoke_snapshot_intro') }}</p>
                <textarea wire:model="revokeReason" class="form-control mb-1" rows="3"
                    placeholder="{{ __('publications.revoke_reason_placeholder') }}"></textarea>
                @error('revokeReason') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                <div class="d-flex gap-2 mt-3">
                    <button wire:click="confirmRevokeSnapshot" class="btn btn-danger flex-grow-1">{{ __('publications.confirm_revoke') }}</button>
                    <button wire:click="cancelRevokeSnapshot" class="btn btn-secondary">{{ __('publications.back') }}</button>
                </div>
            </div>
        </div>
    @endif

    @include('livewire.admin._partials.page-styles')
</div>
