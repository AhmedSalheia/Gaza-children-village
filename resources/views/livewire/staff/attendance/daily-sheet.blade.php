<div class="p-6 space-y-6" dir="rtl">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('attendance.daily_sheet_title') }}</h1>
            @if($sheet)
                <p class="text-sm text-gray-500 mt-1">
                    {{ $sheet->attendance_date->format('l، j F Y') }}
                    · {{ __('attendance.status_label') }}
                    <span class="font-medium {{ $sheet->status->value === 'verified'  ? 'text-green-600'
                        : ($sheet->status->value === 'returned'  ? 'text-red-600'
                        : ($sheet->status->value === 'submitted' ? 'text-blue-600'
                        : 'text-yellow-600')) }}">
                        {{ ucfirst(str_replace('_', ' ', $sheet->status->value)) }}
                    </span>
                </p>
            @endif
        </div>
        <a href="{{ route('staff.attendance.index') }}" class="text-sm text-indigo-600 hover:underline">← {{ __('attendance.my_classes') }}</a>
    </div>

    {{-- Flash --}}
    @if($flashMessage)
        <div class="rounded px-4 py-3 text-sm {{ $flashType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ $flashMessage }}
        </div>
    @endif

    @if(! $sheet)
        <p class="text-sm text-gray-500">{{ __('attendance.loading_sheet') }}</p>
    @else

        {{-- Return reason banner --}}
        @if($sheet->status->value === 'returned' && $sheet->return_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                <strong>{{ __('attendance.returned_by_secretary') }}</strong> {{ $sheet->return_reason }}
            </div>
        @endif

        {{-- Secretary: return / verify actions (submitted or reopened) --}}
        @if($canManage && $sheet->status->awaitingReview())
            <div class="flex gap-3">
                <button wire:click="verify"
                        class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                    {{ $sheet->status->value === 'reopened' ? __('attendance.reverify_sheet') : __('attendance.verify_sheet') }}
                </button>
                <button wire:click="startReturn"
                        class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                    {{ __('attendance.return_to_teacher') }}
                </button>
            </div>
        @endif

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

        {{-- Teacher: bulk mark + submit (draft / returned only) --}}
        @if($sheet->status->isEditable())
            <div class="flex gap-3">
                <button wire:click="bulkMarkPresent"
                        class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded text-sm hover:bg-indigo-200">
                    {{ __('attendance.bulk_mark_present') }}
                </button>
                <button wire:click="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">
                    {{ __('attendance.submit_for_review') }}
                </button>
            </div>
        @endif

        {{-- Attendance table --}}
        @if($records->isEmpty())
            <p class="text-sm text-gray-500">{{ __('attendance.no_students_enrolled') }}</p>
        @else
            {{-- Pass status catalogue as JSON for Alpine per-row logic --}}
            @php $catalogueJson = json_encode($statuses, JSON_UNESCAPED_UNICODE) @endphp

            <div class="bg-white rounded-lg shadow overflow-visible">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-right w-1/4">{{ __('attendance.student') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('ui.status') }}</th>
                            @if($sheet->status->isEditable())
                                <th class="px-4 py-3 text-right">{{ __('attendance.reason_time') }}</th>
                                <th class="px-4 py-3 w-16"></th>
                            @else
                                <th class="px-4 py-3 text-right">{{ __('ui.reason') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('attendance.history') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($records as $record)
                            @if($sheet->status->isEditable())
                                {{-- ── Editable row with Alpine.js per-row state ── --}}
                                <tr x-data="{
                                        enrollmentId: {{ $record->enrollment_id }},
                                        statusCode:   '{{ $record->status_code ?? '' }}',
                                        reason:       @js($record->reason ?? ''),
                                        arrivedAt:    '{{ $record->arrived_at ?? '' }}',
                                        departedAt:   '{{ $record->departed_at ?? '' }}',
                                        catalogue:    {{ $catalogueJson }},
                                        get meta()        { return this.catalogue[this.statusCode] || {}; },
                                        get needsReason() { return !!this.meta.requires_reason; },
                                        get needsArrival(){ return !!this.meta.allows_arrival_time; },
                                        get needsDepart() { return !!this.meta.allows_departure_time; },
                                        save() {
                                            $wire.saveRow(
                                                this.enrollmentId,
                                                this.statusCode,
                                                this.reason,
                                                this.arrivedAt,
                                                this.departedAt
                                            );
                                        }
                                    }"
                                    class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-right font-medium align-top pt-4">{{ $record->student_name }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <select x-model="statusCode"
                                                class="border rounded px-2 py-1.5 text-sm w-full">
                                            <option value="">{{ __('attendance.choose_option') }}</option>
                                            @foreach($statuses as $code => $meta)
                                                <option value="{{ $code }}">
                                                    {{ $meta['label_ar'] }} / {{ $meta['label_en'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 align-top space-y-1.5">
                                        {{-- Reason (shown when status requires_reason) --}}
                                        <div x-show="needsReason">
                                            <input type="text" x-model="reason"
                                                   placeholder="{{ __('attendance.reason_required_placeholder') }}"
                                                   class="border rounded px-2 py-1.5 text-sm w-full" />
                                        </div>
                                        {{-- Arrival time (shown for LATE) --}}
                                        <div x-show="needsArrival">
                                            <label class="text-xs text-gray-500">{{ __('attendance.arrived_at') }}</label>
                                            <input type="time" x-model="arrivedAt"
                                                   class="border rounded px-2 py-1.5 text-sm w-full" />
                                        </div>
                                        {{-- Departure time (shown for LEFT_EARLY) --}}
                                        <div x-show="needsDepart">
                                            <label class="text-xs text-gray-500">{{ __('attendance.left_at') }}</label>
                                            <input type="time" x-model="departedAt"
                                                   class="border rounded px-2 py-1.5 text-sm w-full" />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <button @click="save()"
                                                class="px-3 py-1.5 bg-indigo-600 text-white rounded text-xs hover:bg-indigo-700 whitespace-nowrap">
                                            {{ __('ui.save') }}
                                        </button>
                                    </td>
                                </tr>
                            @else
                                {{-- ── Read-only row ── --}}
                                <tr class="hover:bg-gray-50 @if($record->previous_status_code) bg-amber-50 @endif">
                                    <td class="px-4 py-3 text-right font-medium">{{ $record->student_name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="px-2 py-0.5 rounded text-xs
                                            {{ ($record->status_code === 'present') ? 'bg-green-100 text-green-700'
                                            : (($record->status_code === 'absent')  ? 'bg-red-100 text-red-700'
                                            : 'bg-yellow-100 text-yellow-700') }}">
                                            {{ $statuses[$record->status_code]['label_ar'] ?? ($record->status_code ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 text-xs">
                                        <div>{{ $record->reason ?? '—' }}</div>
                                        @if($record->arrived_at)
                                            <div class="text-gray-400">{{ __('attendance.arrived_prefix') }} {{ $record->arrived_at }}</div>
                                        @endif
                                        @if($record->departed_at)
                                            <div class="text-gray-400">{{ __('attendance.left_prefix') }} {{ $record->departed_at }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-400">
                                        @if($record->previous_status_code)
                                            <span class="line-through">
                                                {{ $statuses[$record->previous_status_code]['label_ar'] ?? $record->previous_status_code }}
                                            </span>
                                            <span class="text-gray-300">→</span>
                                            {{ __('attendance.corrected') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
