{{-- Flash message partial — include in every Livewire admin view --}}
@if($flashMessage !== '')
    <div
        class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 4000)"
        role="alert"
    >
        {{ $flashMessage }}
    </div>
@endif
