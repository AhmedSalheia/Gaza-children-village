<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('attendance.queue_title') }}</h1>
        <span class="text-sm text-gray-500">{{ __('attendance.sheets_awaiting_review') }}</span>
    </div>

    @if($sheets->isEmpty())
        <div class="bg-green-50 border border-green-200 rounded-lg p-5 text-sm text-green-800">
            {{ __('attendance.no_sheets_pending') }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">{{ __('ui.date') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('attendance.class') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('ui.level') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('ui.students') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('ui.submitted') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sheets as $sheet)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-right font-medium">{{ $sheet->attendance_date }}</td>
                            <td class="px-4 py-3 text-right">{{ $sheet->class_name }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $sheet->level_name }}</td>
                            <td class="px-4 py-3 text-right">{{ $sheet->total_students }}</td>
                            <td class="px-4 py-3 text-right text-gray-500 text-xs">
                                {{ $sheet->submitted_at ? \Carbon\Carbon::parse($sheet->submitted_at)->diffForHumans() : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('staff.attendance.verify', ['sheetId' => $sheet->id]) }}"
                                   class="text-indigo-600 hover:underline text-xs">{{ __('ui.review') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
