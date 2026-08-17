@php
    /**
     * Admin portal — management inbox for formal institution requests.
     * Wire model: App\Livewire\Admin\FormalRequests\ManagementInbox
     */
@endphp
<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('requests.formal_management_inbox') }}</h1>
    </div>

    @if($flashMessage)
        <div class="mb-4 rounded border border-blue-300 bg-blue-50 px-4 py-2 text-sm text-blue-800">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-4 flex gap-3">
        <select wire:model.live="statusFilter"
                class="rounded border border-gray-300 px-3 py-1.5 text-sm">
            <option value="">{{ __('ui.all_statuses') }}</option>
            @foreach($statusOptions as $s)
                <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>

        <select wire:model.live="institutionFilter"
                class="rounded border border-gray-300 px-3 py-1.5 text-sm">
            <option value="">{{ __('ui.all_institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}">{{ $inst->name_en }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('ui.document_number') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('ui.institution') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('requests.title') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('ui.type') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('ui.status') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('requests.priority') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $req->request_number }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $req->institution_id }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $req->title_en }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ ucwords($req->request_type) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700">
                                {{ ucwords(str_replace('_', ' ', $req->current_status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ ['', __('requests.priority_low'), __('requests.priority_medium'), __('requests.priority_high'), __('requests.priority_urgent')][$req->priority] ?? $req->priority }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.formal-requests.review', $req->id) }}"
                               class="text-blue-600 hover:underline text-xs">{{ __('ui.review') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                            {{ __('requests.no_management_requests') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
