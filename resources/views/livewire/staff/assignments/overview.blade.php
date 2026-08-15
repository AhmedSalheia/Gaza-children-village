<div class="p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Teaching &amp; Homeroom Assignments</h1>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b">
        <button wire:click="$set('tab', 'teaching')"
                class="px-5 py-2 text-sm font-medium border-b-2 -mb-px
                    {{ $tab === 'teaching' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Teaching
        </button>
        <button wire:click="$set('tab', 'homeroom')"
                class="px-5 py-2 text-sm font-medium border-b-2 -mb-px
                    {{ $tab === 'homeroom' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Homeroom
        </button>
    </div>

    {{-- Teaching tab --}}
    @if($tab === 'teaching')
        @if($teachingAssignments->isEmpty())
            <p class="text-sm text-gray-500">No active teaching assignments for this semester.</p>
        @else
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-right">Class</th>
                            <th class="px-4 py-3 text-right">Level</th>
                            <th class="px-4 py-3 text-right">Subject</th>
                            <th class="px-4 py-3 text-right">Teacher</th>
                            <th class="px-4 py-3 text-right">From</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($teachingAssignments as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-right font-medium">{{ $row->class_group_name }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $row->level_name }}</td>
                                <td class="px-4 py-3 text-right">{{ $row->subject_name }}</td>
                                <td class="px-4 py-3 text-right">{{ $row->staff_name }}</td>
                                <td class="px-4 py-3 text-right">{{ $row->starts_on }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    {{-- Homeroom tab --}}
    @if($tab === 'homeroom')
        @if($homeroomAssignments->isEmpty())
            <p class="text-sm text-gray-500">No active homeroom assignments for this semester.</p>
        @else
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-right">Class</th>
                            <th class="px-4 py-3 text-right">Level</th>
                            <th class="px-4 py-3 text-right">Homeroom Teacher</th>
                            <th class="px-4 py-3 text-right">Role</th>
                            <th class="px-4 py-3 text-right">From</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($homeroomAssignments as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-right font-medium">{{ $row->class_group_name }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $row->level_name }}</td>
                                <td class="px-4 py-3 text-right">{{ $row->staff_name }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $row->is_co_lead ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $row->is_co_lead ? 'Co-lead' : 'Lead' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">{{ $row->starts_on }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
