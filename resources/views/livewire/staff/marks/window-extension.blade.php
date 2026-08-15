@php /** @var \App\Livewire\Staff\Marks\MarkWindowExtension $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Extend Mark-Entry Windows</h1>
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            {{ $flashMessage }}
        </div>
    @endif

    @if($extendingId > 0)
        <div class="card" style="margin-block-end:var(--space-4);border-color:var(--color-primary)">
            <h3 style="margin-block-end:var(--space-3)">Extend Window</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">New Closes At</label>
                    <input wire:model="newClosesAt" type="datetime-local" class="form-control @error('newClosesAt') form-control--error @enderror">
                    @error('newClosesAt')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">Reason</label>
                    <input wire:model="extendReason" type="text" class="form-control @error('extendReason') form-control--error @enderror" placeholder="Reason for extension…">
                    @error('extendReason')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-2)">
                <button wire:click="confirmExtend" class="btn btn--primary btn--sm">Confirm Extension</button>
                <button wire:click="cancelExtend" class="btn btn--outline btn--sm">Cancel</button>
            </div>
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Window</th>
                    <th>Scope</th>
                    <th>Current Closes At</th>
                    <th>Status</th>
                    <th>Extensions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($openWindows as $window)
                    @php $history = $window->extension_history ? json_decode($window->extension_history, true) : []; @endphp
                    <tr>
                        <td dir="rtl">{{ $window->name_ar ?? 'Window #' . $window->id }}</td>
                        <td>{{ $window->class_group_name ?? 'All' }} / {{ $window->subject_name ?? 'All' }}</td>
                        <td>{{ \Carbon\Carbon::parse($window->closes_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge badge--active">{{ $window->status }}</span>
                        </td>
                        <td>{{ count($history) }} extension(s)</td>
                        <td>
                            <button wire:click="startExtend({{ $window->id }})" class="btn btn--primary btn--sm">Extend</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">No open windows in your current semester.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
