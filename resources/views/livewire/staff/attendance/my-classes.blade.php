<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('attendance.my_classes_title') }}</h1>
        <span class="text-sm text-gray-500">{{ now()->format('l، j F Y') }}</span>
    </div>

    @if($classes->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5 text-sm text-yellow-800">
            {{ __('attendance.no_homeroom_assignments') }}
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($classes as $class)
                <div class="bg-white rounded-lg shadow p-5 space-y-3">
                    <div>
                        <h2 class="font-semibold text-gray-800 text-lg">{{ $class->class_name }}</h2>
                        <p class="text-xs text-gray-500">{{ $class->level_name }}
                            @if($class->is_co_lead)
                                · <span class="text-yellow-600">{{ __('assignments.co_lead') }}</span>
                            @else
                                · <span class="text-blue-600">{{ __('assignments.lead') }}</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        @if($class->sheet_status)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $class->sheet_status === 'verified'   ? 'bg-green-100 text-green-700'
                                 : ($class->sheet_status === 'submitted'  ? 'bg-blue-100 text-blue-700'
                                 : ($class->sheet_status === 'returned'   ? 'bg-red-100 text-red-700'
                                 : ($class->sheet_status === 'reopened'   ? 'bg-purple-100 text-purple-700'
                                 : 'bg-yellow-100 text-yellow-700'))) }}">
                                {{ ucfirst(str_replace('_', ' ', $class->sheet_status)) }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                {{ __('attendance.no_sheet_yet') }}
                            </span>
                        @endif
                    </div>

                    @if($class->sheet_id)
                        <a href="{{ route('staff.attendance.sheet', ['sheetId' => $class->sheet_id]) }}"
                           class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">
                            {{ in_array($class->sheet_status, ['draft', 'returned']) ? __('attendance.continue_entry') : __('attendance.view_sheet') }}
                        </a>
                    @else
                        <a href="{{ route('staff.attendance.sheet', ['classGroupId' => $class->class_group_id, 'date' => $class->today]) }}"
                           class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                            {{ __('attendance.open_todays_sheet') }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
