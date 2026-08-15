<div class="space-y-6">

    {{-- Flash --}}
    @if($flashMessage)
        <div class="px-4 py-3 rounded text-sm {{ $flashType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Date') }}</label>
            <input type="date"
                   wire:model.live="filterDate"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }}</label>
            <select wire:model.live="filterStatus"
                    class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                <option value="pending">{{ __('Pending') }}</option>
                <option value="accepted">{{ __('Accepted') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="">{{ __('All') }}</option>
            </select>
        </div>
    </div>

    {{-- Review Modal --}}
    @if($reviewingEvent)
        <div class="fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Review Scan Event') }}</h3>

                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">{{ __('Time') }}:</span> {{ \Carbon\Carbon::parse($reviewingEvent->scanned_at)->format('H:i:s') }}</div>
                    <div><span class="text-gray-500">{{ __('Direction candidate') }}:</span> {{ ucfirst($reviewingEvent->direction) }}</div>
                    @if($reviewingEvent->device_fingerprint)
                        <div><span class="text-gray-500">{{ __('Device') }}:</span> {{ $reviewingEvent->device_fingerprint }}</div>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Confirm Direction') }}</label>
                    <select wire:model="confirmedDirection"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full">
                        <option value="">{{ __('Keep original') }} ({{ $reviewingEvent->direction }})</option>
                        <option value="arrival">{{ __('Arrival') }}</option>
                        <option value="departure">{{ __('Departure') }}</option>
                        <option value="unknown">{{ __('Unknown') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ __('Decision') }}</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-1.5 text-sm">
                            <input type="radio" wire:model="reviewOutcome" value="accepted"> {{ __('Accept') }}
                        </label>
                        <label class="flex items-center gap-1.5 text-sm">
                            <input type="radio" wire:model="reviewOutcome" value="rejected"> {{ __('Reject') }}
                        </label>
                    </div>
                </div>

                @if($reviewOutcome === 'rejected')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Rejection reason') }}</label>
                        <textarea wire:model="rejectionReason"
                                  rows="2"
                                  class="border border-gray-300 rounded px-3 py-2 text-sm w-full resize-none"
                                  placeholder="{{ __('Required') }}"></textarea>
                    </div>
                @endif

                <div class="flex gap-3 pt-2">
                    <button wire:click="submitReview"
                            :disabled="!$wire.reviewOutcome"
                            class="px-4 py-2 bg-teal-600 text-white text-sm rounded hover:bg-teal-700 disabled:opacity-50">
                        {{ __('Confirm') }}
                    </button>
                    <button wire:click="cancelReview"
                            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Events Table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Staff') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Scanned At') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Direction') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($events as $event)
                    <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                        <td class="px-4 py-3 text-right">{{ $event->staff_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-xs text-gray-600">
                            {{ \Carbon\Carbon::parse($event->scanned_at)->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php $dir = $event->direction; @endphp
                            <span class="text-xs px-1.5 py-0.5 rounded
                                {{ $dir === 'arrival' ? 'bg-teal-50 text-teal-700' : ($dir === 'departure' ? 'bg-orange-50 text-orange-700' : 'bg-gray-100 text-gray-500') }}">
                                {{ ucfirst($dir) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php $status = $event->processing_status; @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ($status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($event->processing_status === 'pending')
                                <button wire:click="startReview({{ $event->id }})"
                                        class="text-xs px-2 py-1 bg-teal-600 text-white rounded hover:bg-teal-700">
                                    {{ __('Review') }}
                                </button>
                            @elseif($event->rejection_reason)
                                <span class="text-xs text-gray-400" title="{{ $event->rejection_reason }}">{{ __('Rejected') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                            {{ __('No scan events for the selected filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
