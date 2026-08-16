@php
    /**
     * Staff portal — formal requests list.
     * Wire model: App\Livewire\Staff\FormalRequests\FormalRequestList
     */
@endphp
<div>
    @if($flashMessage)
        <div class="mb-4 rounded border border-blue-300 bg-blue-50 px-4 py-2 text-sm text-blue-800">
            {{ $flashMessage }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Formal Requests</h1>
        @if($canPrepare)
            <a href="{{ route('staff.formal-requests.new') }}"
               class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                New Request
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex gap-3">
        <select wire:model.live="statusFilter"
                class="rounded border border-gray-300 px-3 py-1.5 text-sm">
            <option value="">All statuses</option>
            @foreach($statusOptions as $s)
                <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>

        <select wire:model.live="typeFilter"
                class="rounded border border-gray-300 px-3 py-1.5 text-sm">
            <option value="">All types</option>
            @foreach($typeOptions as $t)
                <option value="{{ $t }}">{{ ucwords($t) }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-start">Number</th>
                    <th class="px-4 py-3 text-start">Title</th>
                    <th class="px-4 py-3 text-start">Type</th>
                    <th class="px-4 py-3 text-start">Status</th>
                    <th class="px-4 py-3 text-start">Priority</th>
                    <th class="px-4 py-3 text-start">Date</th>
                    <th class="px-4 py-3 text-start">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $req->request_number }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $req->title_en }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ ucwords($req->request_type) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                @if(in_array($req->current_status, ['closed','cancelled','superseded'])) bg-gray-100 text-gray-600
                                @elseif($req->current_status === 'signed') bg-green-100 text-green-700
                                @elseif(str_contains($req->current_status, 'review')) bg-yellow-100 text-yellow-700
                                @elseif(str_contains($req->current_status, 'returned')) bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ ucwords(str_replace('_', ' ', $req->current_status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ ['', 'Low', 'Medium', 'High', 'Urgent'][$req->priority] ?? $req->priority }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('staff.formal-requests.detail', $req->id) }}"
                               class="text-blue-600 hover:underline text-xs me-2">View</a>
                            @if($canPrepare && $req->isCancellable())
                                <button wire:click="cancel({{ $req->id }})"
                                        wire:confirm="Cancel this request?"
                                        class="text-red-600 hover:underline text-xs">Cancel</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                            No formal requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
