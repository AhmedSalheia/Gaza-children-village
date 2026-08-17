<div class="p-6 space-y-6">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('assignments.teaching_title') }}</h1>
        @if($canManage)
            <button wire:click="$set('showForm', true)"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                + {{ __('assignments.new_assignment') }}
            </button>
        @endif
    </div>

    {{-- Flash --}}
    @if($flashMessage)
        <div class="rounded px-4 py-3 text-sm {{ $flashType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.institution_semesters') }}</label>
            <select wire:model.live="instSemId" class="border rounded px-3 py-2 text-sm w-72">
                <option value="0">{{ __('assignments.select_semester') }}</option>
                @foreach($openSemesters as $sem)
                    <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }} ({{ $sem->status }})</option>
                @endforeach
            </select>
        </div>

        @if($instSemId)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.class_group') }}</label>
                <select wire:model.live="classGroupId" class="border rounded px-3 py-2 text-sm w-48">
                    <option value="0">{{ __('assignments.all_classes') }}</option>
                    @foreach($classGroups as $cg)
                        <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model.live="showHistory" class="rounded">
            {{ __('assignments.show_history') }}
        </label>
    </div>

    {{-- Create form --}}
    @if($showForm && $canManage)
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-5 space-y-4">
            <h2 class="font-semibold text-indigo-800">{{ __('assignments.new_teaching_assignment') }}</h2>

            @if($instSemId === 0)
                <p class="text-sm text-indigo-600">{{ __('assignments.select_semester_first') }}</p>
            @else
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.staff_position_teacher') }}</label>
                        <select wire:model="formPositionId" class="border rounded px-3 py-2 text-sm w-full">
                            <option value="0">{{ __('assignments.select_position') }}</option>
                            @foreach($eligiblePositions as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->staff_name }} ({{ $pos->position_definition }})</option>
                            @endforeach
                        </select>
                        @error('formPositionId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.class_group') }}</label>
                        <select wire:model="formClassGroupId" class="border rounded px-3 py-2 text-sm w-full">
                            <option value="0">{{ __('assignments.select_class') }}</option>
                            @foreach($classGroups as $cg)
                                <option value="{{ $cg->id }}">{{ $cg->name_ar }} ({{ $cg->level_name }})</option>
                            @endforeach
                        </select>
                        @error('formClassGroupId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.subject_offering') }}</label>
                        <select wire:model="formSubjectId" class="border rounded px-3 py-2 text-sm w-full">
                            <option value="0">{{ __('assignments.select_subject') }}</option>
                            @foreach($subjectOfferings as $so)
                                <option value="{{ $so->id }}">{{ $so->subject_name }}{{ $so->subject_name_en ? ' / '.$so->subject_name_en : '' }}</option>
                            @endforeach
                        </select>
                        @error('formSubjectId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.starts_on') }}</label>
                        <input type="date" wire:model="formStartsOn" class="border rounded px-3 py-2 text-sm w-full">
                        @error('formStartsOn') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex gap-3">
                    <button wire:click="save" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">{{ __('ui.save') }}</button>
                    <button wire:click="$set('showForm', false)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">{{ __('ui.cancel') }}</button>
                </div>
            @endif
        </div>
    @endif

    {{-- End assignment panel --}}
    @if($endingId)
        <div class="bg-red-50 border border-red-200 rounded-lg p-5 space-y-4">
            <h2 class="font-semibold text-red-800">{{ __('assignments.end_teaching_assignment', ['id' => $endingId]) }}</h2>
            <p class="text-xs text-red-700">{!! __('assignments.end_notice') !!}</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.end_date') }}</label>
                    <input type="date" wire:model="endDate" class="border rounded px-3 py-2 text-sm w-full">
                    @error('endDate') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('ui.reason') }}</label>
                    <input type="text" wire:model="endReason" placeholder="{{ __('assignments.reason_ending_placeholder') }}"
                           class="border rounded px-3 py-2 text-sm w-full">
                    @error('endReason') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="confirmEnd" class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">{{ __('assignments.confirm_end') }}</button>
                <button wire:click="cancelEnd" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    {{-- Replace assignment panel --}}
    @if($replacingId)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 space-y-4">
            <h2 class="font-semibold text-amber-800">{{ __('assignments.replace_teaching_assignment', ['id' => $replacingId]) }}</h2>
            <p class="text-xs text-amber-700">
                {!! __('assignments.replace_notice') !!}
            </p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.replacement_teacher') }}</label>
                    <select wire:model="replacePositionId" class="border rounded px-3 py-2 text-sm w-full">
                        <option value="0">{{ __('assignments.select_new_position') }}</option>
                        @foreach($eligiblePositions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->staff_name }} ({{ $pos->position_definition }})</option>
                        @endforeach
                    </select>
                    @error('replacePositionId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.effective_date') }}</label>
                    <input type="date" wire:model="replaceDate" class="border rounded px-3 py-2 text-sm w-full">
                    @error('replaceDate') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('assignments.reason_replacement') }}</label>
                    <input type="text" wire:model="replaceReason" placeholder="{{ __('assignments.reason_replacement_placeholder') }}"
                           class="border rounded px-3 py-2 text-sm w-full">
                    @error('replaceReason') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="confirmReplace"
                        class="px-4 py-2 bg-amber-600 text-white rounded text-sm hover:bg-amber-700">
                    {{ __('assignments.confirm_replace') }}
                </button>
                <button wire:click="cancelReplace" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    {{-- Table --}}
    @if($instSemId === 0)
        <p class="text-sm text-gray-500">{{ __('assignments.select_semester_to_view') }}</p>
    @elseif($assignments instanceof \Illuminate\Support\Collection ? $assignments->isEmpty() : $assignments->total() === 0)
        <p class="text-sm text-gray-500">{{ __('assignments.no_assignments') }}</p>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">{{ __('ui.class_group') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('ui.subject') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('assignments.teacher') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('assignments.from') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('assignments.to') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('ui.status') }}</th>
                        @if($canManage)<th class="px-4 py-3"></th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($assignments as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-right font-medium">{{ $row->class_group_name }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->subject_name }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->staff_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->starts_on }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->ends_on ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="px-2 py-0.5 rounded text-xs
                                    {{ $row->status === 'active' ? 'bg-green-100 text-green-700' : ($row->status === 'superseded' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $row->status }}
                                </span>
                            </td>
                            @if($canManage)
                                <td class="px-4 py-3 text-right space-x-2">
                                    @if($row->status === 'active')
                                        <button wire:click="startEnd({{ $row->id }})"
                                                class="text-xs text-red-600 hover:underline">{{ __('ui.end') }}</button>
                                        <button wire:click="startReplace({{ $row->id }})"
                                                class="text-xs text-amber-600 hover:underline">{{ __('assignments.replace') }}</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($assignments, 'links'))
            <div class="mt-4">{{ $assignments->links() }}</div>
        @endif
    @endif
</div>
