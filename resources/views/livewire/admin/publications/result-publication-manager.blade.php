{{-- Livewire: App\Livewire\Admin\Publications\ResultPublicationManager --}}
<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('publications.publish_results_title') }}</h1>
        <p class="page-subtitle">{{ __('publications.publish_results_subtitle') }}</p>
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
            {{-- Class group selector + publish card --}}
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header">{{ __('publications.publish_group_results') }}</div>
                    <div class="card-body">
                        <label class="form-label">{{ __('publications.class_group') }}</label>
                        <select wire:model.live="classGroupId" class="form-select mb-3">
                            <option value="0">{{ __('publications.select_group') }}</option>
                            @foreach($classGroups as $cg)
                                <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                            @endforeach
                        </select>

                        @if($classGroupId > 0 && $readiness)
                            <div class="alert {{ $readiness->ready ? 'alert-success' : 'alert-warning' }} py-2 mb-3">
                                <strong>{{ __('publications.sheets_status') }}</strong>
                                {{ __('publications.approved_of_total', ['approved' => $readiness->approved, 'total' => $readiness->total]) }}
                                @if($readiness->outstanding > 0)
                                    <div class="small mt-1">{{ __('publications.outstanding_sheets', ['count' => $readiness->outstanding]) }}</div>
                                @endif
                            </div>

                            @if($canPublish)
                                <button wire:click="publish" wire:confirm="{{ __('publications.publish_confirm') }}"
                                    class="btn btn-primary w-100"
                                    @unless($readiness->ready) disabled @endunless>
                                    {{ __('publications.publish_results') }}
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Publication history --}}
            <div class="col-md-7">
                @if($classGroupId > 0)
                    <div class="card">
                        <div class="card-header">{{ __('publications.version_history') }}</div>
                        <div class="card-body p-0">
                            @forelse($publications as $pub)
                                <div class="border-bottom p-3 {{ $pub->status === 'revoked' ? 'opacity-50' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge {{ $pub->status === 'published' && ! $pub->superseded_by_id ? 'bg-success' : ($pub->status === 'revoked' ? 'bg-danger' : 'bg-secondary') }} me-2">
                                                {{ __('publications.version_label', ['version' => $pub->version]) }}
                                                @if($pub->superseded_by_id) ({{ __('publications.superseded') }}) @endif
                                                @if($pub->status === 'revoked') ({{ __('publications.revoked') }}) @endif
                                            </span>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($pub->published_at)->format('Y-m-d H:i') }}</small>
                                        </div>
                                        @if($pub->status === 'published' && $canRevoke)
                                            <button wire:click="startRevoke({{ $pub->id }})" class="btn btn-sm btn-outline-danger">
                                                {{ __('publications.revoke') }}
                                            </button>
                                        @endif
                                    </div>
                                    @if($pub->status === 'revoked')
                                        <div class="mt-1 small text-danger">{{ __('publications.reason_label') }} {{ $pub->revoke_reason }}</div>
                                    @endif
                                </div>
                            @empty
                                <div class="p-3 text-muted">{{ __('publications.no_versions') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Revoke modal --}}
    @if($revokingId > 0)
        <div class="modal-backdrop-light"></div>
        <div class="card position-fixed top-50 start-50 translate-middle shadow-lg" style="width:460px;z-index:1055">
            <div class="card-header text-danger">{{ __('publications.revoke_publication_title') }}</div>
            <div class="card-body">
                <p>{{ __('publications.revoke_intro') }}</p>
                <textarea wire:model="revokeReason" class="form-control mb-1" rows="3"
                    placeholder="{{ __('publications.revoke_reason_placeholder') }}"></textarea>
                @error('revokeReason') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                <div class="d-flex gap-2 mt-3">
                    <button wire:click="confirmRevoke" class="btn btn-danger flex-grow-1">{{ __('publications.confirm_revoke') }}</button>
                    <button wire:click="cancelRevoke" class="btn btn-secondary">{{ __('publications.back') }}</button>
                </div>
            </div>
        </div>
    @endif

    @include('livewire.admin._partials.page-styles')
</div>
