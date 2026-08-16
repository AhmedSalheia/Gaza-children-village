<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ __('ui.my_document_requests', [], null, 'My Document Requests') }}</h1>
        <a href="{{ route('guardian.documents.new') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            + {{ __('ui.new_request', [], null, 'New Request') }}
        </a>
    </div>

    @if ($requests->isEmpty())
        <div class="text-center py-16 text-gray-500">
            <p class="text-lg">{{ __('ui.no_document_requests', [], null, 'No document requests yet.') }}</p>
        </div>
    @else
        <div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3">{{ __('ui.student', [], null, 'Student') }}</th>
                        <th class="px-4 py-3">{{ __('ui.document_type', [], null, 'Document Type') }}</th>
                        <th class="px-4 py-3">{{ __('ui.status', [], null, 'Status') }}</th>
                        <th class="px-4 py-3">{{ __('ui.document_number', [], null, 'Number') }}</th>
                        <th class="px-4 py-3">{{ __('ui.date', [], null, 'Date') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($requests as $req)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $req->student_name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $req->document_type_code }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ in_array($req->status, ['issued']) ? 'bg-green-100 text-green-800' : '' }}
                                    {{ in_array($req->status, ['rejected','cancelled','generation_failed']) ? 'bg-red-100 text-red-800' : '' }}
                                    {{ in_array($req->status, ['submitted','pending_completeness','completeness_passed','awaiting_approval','approved','generating']) ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ in_array($req->status, ['pending_clarification']) ? 'bg-orange-100 text-orange-800' : '' }}
                                    {{ in_array($req->status, ['completeness_failed']) ? 'bg-red-50 text-red-700' : '' }}
                                ">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $req->document_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('guardian.documents.detail', $req->id) }}"
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    {{ __('ui.view', [], null, 'View') }}
                                </a>
                                @if ($req->issued_document_id)
                                    &middot;
                                    <a href="{{ route('guardian.documents.download', $req->issued_document_id) }}"
                                       class="text-green-600 hover:text-green-900 text-sm font-medium">
                                        {{ __('ui.download', [], null, 'Download') }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
