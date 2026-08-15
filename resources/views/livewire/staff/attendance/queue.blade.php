<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Attendance Queue</h1>
        <span class="text-sm text-gray-500">Sheets awaiting your review</span>
    </div>

    @if($sheets->isEmpty())
        <div class="bg-green-50 border border-green-200 rounded-lg p-5 text-sm text-green-800">
            No attendance sheets are pending review. All caught up!
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">Date</th>
                        <th class="px-4 py-3 text-right">Class</th>
                        <th class="px-4 py-3 text-right">Level</th>
                        <th class="px-4 py-3 text-right">Students</th>
                        <th class="px-4 py-3 text-right">Submitted</th>
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
                                   class="text-indigo-600 hover:underline text-xs">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
