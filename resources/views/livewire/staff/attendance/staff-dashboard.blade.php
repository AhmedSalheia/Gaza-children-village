<div class="space-y-6">

    {{-- Date filter --}}
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.date') }}</label>
        <input type="date"
               wire:model.live="selectedDate"
               class="border border-gray-300 rounded px-3 py-1.5 text-sm">
    </div>

    {{-- Period summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($summaries as $summary)
            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm cursor-pointer
                        {{ $selectedPeriodId === $summary->period_id ? 'ring-2 ring-teal-500' : '' }}"
                 wire:click="$set('selectedPeriodId', {{ $summary->period_id }})">
                <div class="font-medium text-gray-800 mb-2">{{ $summary->period_name }}</div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div>
                        <div class="text-2xl font-bold text-green-600">{{ $summary->present }}</div>
                        <div class="text-gray-400">{{ __('ui.present') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-500">{{ $summary->absent }}</div>
                        <div class="text-gray-400">{{ __('ui.absent') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-400">{{ $summary->unrecorded }}</div>
                        <div class="text-gray-400">{{ __('ui.attend_not_recorded') }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center text-gray-400 py-8">{{ __('ui.attend_no_periods') }}</div>
        @endforelse
    </div>

    {{-- Detail table --}}
    @if($selectedPeriodId)
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.name') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.status') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.attend_arrival') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.attend_qr_scan') }}</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">{{ __('ui.verified') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attendanceRows as $row)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                            <td class="px-4 py-3 font-medium text-right">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-right text-xs">{{ $row->status_code ? ucfirst(str_replace('_', ' ', $row->status_code)) : '—' }}</td>
                            <td class="px-4 py-3 text-right text-xs text-gray-500">{{ $row->confirmed_arrived_at ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-xs text-gray-400">
                                @if($row->scanned_arrived_at)
                                    <span class="text-teal-600">▲ {{ $row->scanned_arrived_at }}</span>
                                @endif
                                @if($row->scanned_departed_at)
                                    <span class="text-orange-500">▼ {{ $row->scanned_departed_at }}</span>
                                @endif
                                @if(!$row->scanned_arrived_at && !$row->scanned_departed_at)
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($row->is_verified)
                                    <span class="text-green-600 text-xs">✓</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">
                                {{ __('ui.attend_no_records_period') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
