<div class="space-y-6">

    {{-- Flash --}}
    @if($flashMessage)
        <div class="px-4 py-3 rounded text-sm {{ $flashType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Correction form (inline modal) --}}
    @if($correctingRecordId)
        <div class="bg-amber-50 border border-amber-300 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-amber-900 text-sm">{{ __('ui.attend_correct_verified') }}</h3>
                <button wire:click="cancelCorrection" class="text-amber-600 hover:text-amber-800 text-xs">{{ __('ui.cancel') }}</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4"
                 x-data="{
                     status: @entangle('correctStatus'),
                     get requiresReason() { return ['excused_absence','leave','official_duty'].includes(this.status); },
                     get allowsArrival()  { return this.status === 'late'; }
                 }">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.attend_new_status') }}</label>
                    <select wire:model.live="correctStatus"
                            x-model="status"
                            class="border border-gray-300 rounded px-2 py-1.5 text-sm w-full">
                        <option value="">— {{ __('ui.attend_select') }} —</option>
                        @foreach($statuses as $code => $meta)
                            <option value="{{ $code }}">{{ $meta['label_ar'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="requiresReason">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.reason') }}</label>
                    <input type="text"
                           wire:model="correctReason"
                           class="border border-gray-300 rounded px-2 py-1.5 text-sm w-full"
                           placeholder="{{ __('ui.required') }}">
                </div>
                <div x-show="allowsArrival">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.attend_confirmed_arrival') }}</label>
                    <input type="time"
                           wire:model="correctArrivedAt"
                           class="border border-gray-300 rounded px-2 py-1.5 text-sm w-full">
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="submitCorrection"
                        wire:confirm="{{ __('ui.attend_save_correction_confirm') }}"
                        class="px-4 py-2 bg-amber-700 text-white text-sm rounded hover:bg-amber-800">
                    {{ __('ui.attend_save_correction') }}
                </button>
                <button wire:click="cancelCorrection"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded hover:bg-gray-50">
                    {{ __('ui.cancel') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.date') }}</label>
            <input type="date"
                   wire:model.live="selectedDate"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.period') }}</label>
            <select wire:model.live="selectedPeriodId"
                    class="border border-gray-300 rounded px-3 py-1.5 text-sm">
                @foreach($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Attendance Grid --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.name') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.reason') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.attend_arrival') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('ui.attend_qr_scan') }}</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">{{ __('ui.verified') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($staffRows as $row)
                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}"
                        x-data="{
                            status: '{{ $row->status_code ?? '' }}',
                            get requiresReason() {
                                return ['excused_absence','leave','official_duty'].includes(this.status);
                            },
                            get allowsArrival() {
                                return this.status === 'late';
                            }
                        }">
                        <td class="px-4 py-3 font-medium text-right">{{ $row->name }}</td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if($row->is_verified)
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">
                                    {{ $statuses[$row->status_code]['label_ar'] ?? $row->status_code }}
                                </span>
                            @else
                                <select
                                    wire:model="rowStatus.{{ $row->staff_profile_id }}"
                                    x-model="status"
                                    class="border border-gray-300 rounded px-2 py-1 text-xs w-full">
                                    <option value="">-- {{ __('ui.attend_select') }} --</option>
                                    @foreach($statuses as $code => $meta)
                                        <option value="{{ $code }}"
                                            {{ $row->status_code === $code ? 'selected' : '' }}>
                                            {{ $meta['label_ar'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </td>

                        {{-- Reason --}}
                        <td class="px-4 py-3">
                            @if($row->is_verified)
                                <span class="text-xs text-gray-500">{{ $row->reason ?? '—' }}</span>
                            @else
                                <input type="text"
                                       wire:model="rowReason.{{ $row->staff_profile_id }}"
                                       x-show="requiresReason"
                                       placeholder="{{ __('ui.required') }}"
                                       class="border border-gray-300 rounded px-2 py-1 text-xs w-full">
                            @endif
                        </td>

                        {{-- Confirmed arrival --}}
                        <td class="px-4 py-3">
                            @if($row->is_verified)
                                <span class="text-xs text-gray-500">{{ $row->confirmed_arrived ?? '—' }}</span>
                            @else
                                <input type="time"
                                       wire:model="rowArrivedAt.{{ $row->staff_profile_id }}"
                                       x-show="allowsArrival"
                                       class="border border-gray-300 rounded px-2 py-1 text-xs">
                            @endif
                        </td>

                        {{-- QR scan times (informational) --}}
                        <td class="px-4 py-3 text-xs text-gray-400">
                            @if($row->scanned_arrived)
                                <span class="text-teal-600">▲ {{ $row->scanned_arrived }}</span>
                            @endif
                            @if($row->scanned_departed)
                                <span class="text-orange-500">▼ {{ $row->scanned_departed }}</span>
                            @endif
                            @if(!$row->scanned_arrived && !$row->scanned_departed)
                                —
                            @endif
                        </td>

                        {{-- Verified badge --}}
                        <td class="px-4 py-3 text-center">
                            @if($row->is_verified)
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">✓</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right space-x-2 rtl:space-x-reverse whitespace-nowrap">
                            @if($row->is_verified)
                                {{-- Correction: opens inline form above the table --}}
                                @if($correctingRecordId !== $row->record_id)
                                    <button wire:click="startCorrection({{ $row->record_id }})"
                                            class="text-xs px-2 py-1 bg-amber-600 text-white rounded hover:bg-amber-700">
                                        {{ __('requests.approve_btn') }}
                                    </button>
                                @else
                                    <span class="text-xs text-amber-600 font-medium">{{ __('ui.attend_editing') }}</span>
                                @endif
                            @else
                                <button wire:click="saveRow({{ $row->staff_profile_id }})"
                                        class="text-xs px-2 py-1 bg-teal-600 text-white rounded hover:bg-teal-700">
                                    {{ __('ui.save') }}
                                </button>
                                @if($row->record_id && $row->status_code)
                                    <button wire:click="verifyRecord({{ $row->record_id }})"
                                            wire:confirm="{{ __('ui.attend_verify_confirm') }}"
                                            class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                        {{ __('ui.verify') }}
                                    </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                            {{ __('ui.attend_no_staff_records') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
