<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('attendance.sheet_verification') }}</h1>
        <a href="{{ route('staff.attendance.queue') }}" class="text-sm text-indigo-600 hover:underline">← {{ __('attendance.queue') }}</a>
    </div>

    {{-- Flash --}}
    @if($flashMessage)
        <div class="rounded px-4 py-3 text-sm {{ $flashType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ $flashMessage }}
        </div>
    @endif

    @if($sheet)
        <div class="bg-white rounded-lg shadow p-4 flex gap-6 text-sm">
            <div><span class="text-gray-500">{{ __('attendance.date_label') }}</span> <strong>{{ $sheet->attendance_date->format('j F Y') }}</strong></div>
            <div><span class="text-gray-500">{{ __('attendance.status_label') }}</span>
                <span class="font-medium {{ $sheet->status->value === 'verified'  ? 'text-green-600'
                    : ($sheet->status->value === 'reopened' ? 'text-purple-600'
                    : ($sheet->status->value === 'returned' ? 'text-red-600'
                    : 'text-blue-600')) }}">
                    {{ ucfirst($sheet->status->value) }}
                </span>
            </div>
        </div>

        {{-- Primary actions --}}
        <div class="flex gap-3 flex-wrap">
            {{-- Verify / re-verify: available for submitted or reopened sheets --}}
            @if($sheet->status->awaitingReview())
                <button wire:click="verify"
                        class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                    {{ $sheet->status->value === 'reopened' ? __('attendance.reverify_sheet') : __('attendance.verify_sheet') }}
                </button>
                <button wire:click="startReturn"
                        class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                    {{ __('attendance.return_to_teacher') }}
                </button>
            @endif

            {{-- Reopen: only for verified sheets --}}
            @if($sheet->status->value === 'verified')
                <button wire:click="reopen"
                        class="px-4 py-2 bg-amber-600 text-white rounded text-sm hover:bg-amber-700">
                    {{ __('attendance.reopen_for_correction') }}
                </button>
            @endif
        </div>

        {{-- Return form --}}
        @if($showReturn)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-3">
                <label class="block text-sm font-medium text-red-800">{{ __('attendance.return_reason') }}</label>
                <textarea wire:model="returnReason" rows="3"
                          class="border rounded px-3 py-2 text-sm w-full"
                          placeholder="{{ __('attendance.return_reason_placeholder') }}"></textarea>
                <div class="flex gap-2">
                    <button wire:click="confirmReturn"
                            class="px-4 py-2 bg-red-600 text-white rounded text-sm">{{ __('attendance.confirm_return') }}</button>
                    <button wire:click="$set('showReturn', false)"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">{{ __('ui.cancel') }}</button>
                </div>
            </div>
        @endif

        {{-- Correction form (reopened sheet only) --}}
        @if($showCorrect)
            @php $correctMeta = $statuses[$correctStatusCode] ?? [] @endphp
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-3">
                <h3 class="font-medium text-amber-800">{{ __('attendance.correct_record') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('attendance.new_status') }}</label>
                        <select wire:model.live="correctStatusCode" class="border rounded px-3 py-2 text-sm w-full">
                            @foreach($statuses as $code => $meta)
                                <option value="{{ $code }}">{{ $meta['label_ar'] }} / {{ $meta['label_en'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reason (always shown for corrections — good practice to document why) --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('attendance.correction_reason') }}
                            @if(! empty($correctMeta['requires_reason'])) <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="text" wire:model="correctReason"
                               class="border rounded px-3 py-2 text-sm w-full"
                               placeholder="{{ __('attendance.correction_reason_placeholder') }}">
                    </div>

                    @if(! empty($correctMeta['allows_arrival_time']))
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('attendance.arrived_at') }}</label>
                            <input type="time" wire:model="correctArrivedAt"
                                   class="border rounded px-3 py-2 text-sm w-full">
                        </div>
                    @endif

                    @if(! empty($correctMeta['allows_departure_time']))
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('attendance.left_at') }}</label>
                            <input type="time" wire:model="correctDepartedAt"
                                   class="border rounded px-3 py-2 text-sm w-full">
                        </div>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button wire:click="confirmCorrect"
                            class="px-4 py-2 bg-amber-600 text-white rounded text-sm">{{ __('attendance.save_correction') }}</button>
                    <button wire:click="$set('showCorrect', false)"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">{{ __('ui.cancel') }}</button>
                </div>
            </div>
        @endif

        {{-- Record table --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">{{ __('attendance.student') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('ui.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('attendance.reason_time') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('attendance.correction_history') }}</th>
                        @if($sheet->allowsCorrection())
                            <th class="px-4 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($records as $record)
                        <tr class="hover:bg-gray-50 @if($record->previous_status_code) bg-amber-50 @endif">
                            <td class="px-4 py-3 text-right font-medium">{{ $record->student_name }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="px-2 py-0.5 rounded text-xs
                                    {{ ($record->status_code === 'present') ? 'bg-green-100 text-green-700'
                                    : (($record->status_code === 'absent')  ? 'bg-red-100 text-red-700'
                                    : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ $statuses[$record->status_code]['label_ar'] ?? $record->status_code ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 text-xs">
                                <div>{{ $record->reason ?? '—' }}</div>
                                @if($record->arrived_at)
                                    <div class="text-gray-400">{{ __('attendance.arrived_prefix') }} {{ $record->arrived_at }}</div>
                                @endif
                                @if($record->departed_at)
                                    <div class="text-gray-400">{{ __('attendance.left_prefix') }} {{ $record->departed_at }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-gray-400">
                                @php $history = $correctionHistory[$record->enrollment_id] ?? [] @endphp
                                @if(count($history) > 0)
                                    <ul class="space-y-1">
                                        @foreach($history as $entry)
                                            <li>
                                                <span class="line-through text-gray-300">
                                                    {{ $statuses[$entry->previous_status_code]['label_ar'] ?? $entry->previous_status_code }}
                                                </span>
                                                <span class="text-gray-300 mx-0.5">→</span>
                                                <span class="text-xs text-gray-500">{{ __('attendance.cycle', ['n' => $entry->correction_cycle]) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    —
                                @endif
                            </td>
                            @if($sheet->allowsCorrection())
                                <td class="px-4 py-3">
                                    <button wire:click="startCorrect({{ $record->enrollment_id }}, '{{ $record->status_code }}')"
                                            class="text-xs text-amber-600 hover:underline whitespace-nowrap">
                                        {{ __('attendance.correct') }}
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
