@php /** @var \App\Livewire\Staff\Marks\MarkWindowExtension $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('marks.extend_windows_title') }}</h1>
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            {{ $flashMessage }}
        </div>
    @endif

    @if($extendingId > 0)
        <div class="card" style="margin-block-end:var(--space-4);border-color:var(--color-primary)">
            <h3 style="margin-block-end:var(--space-3)">{{ __('marks.extend_window') }}</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('marks.new_closes_at') }}</label>
                    <input wire:model="newClosesAt" type="datetime-local" class="form-control @error('newClosesAt') form-control--error @enderror">
                    @error('newClosesAt')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.reason') }}</label>
                    <input wire:model="extendReason" type="text" class="form-control @error('extendReason') form-control--error @enderror" placeholder="{{ __('marks.extension_reason_placeholder') }}">
                    @error('extendReason')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-2)">
                <button wire:click="confirmExtend" class="btn btn--primary btn--sm">{{ __('marks.confirm_extension') }}</button>
                <button wire:click="cancelExtend" class="btn btn--outline btn--sm">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('marks.window') }}</th>
                    <th>{{ __('marks.scope') }}</th>
                    <th>{{ __('marks.current_closes_at') }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th>{{ __('marks.extensions') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($openWindows as $window)
                    @php $history = $window->extension_history ? json_decode($window->extension_history, true) : []; @endphp
                    <tr>
                        <td dir="rtl">{{ $window->name_ar ?? __('marks.window_number', ['id' => $window->id]) }}</td>
                        <td>{{ $window->class_group_name ?? __('marks.all') }} / {{ $window->subject_name ?? __('marks.all') }}</td>
                        <td>{{ \Carbon\Carbon::parse($window->closes_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge badge--active">{{ $window->status }}</span>
                        </td>
                        <td>{{ __('marks.extensions_count', ['count' => count($history)]) }}</td>
                        <td>
                            <button wire:click="startExtend({{ $window->id }})" class="btn btn--primary btn--sm">{{ __('marks.extend') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('marks.no_open_windows') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
