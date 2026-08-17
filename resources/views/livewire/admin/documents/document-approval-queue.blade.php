<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">{{ __('documents.approval_queue_title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('documents.approval_queue_subtitle') }}</p>
    </div>

    @if ($requests->isEmpty())
        <div class="text-center py-16 text-gray-500">
            <p>{{ __('documents.no_approval_requests') }}</p>
        </div>
    @else
        <div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3">{{ __('documents.student') }}</th>
                        <th class="px-4 py-3">{{ __('documents.document_type') }}</th>
                        <th class="px-4 py-3">{{ __('documents.school') }}</th>
                        <th class="px-4 py-3">{{ __('documents.submitted_date') }}</th>
                        <th class="px-4 py-3">{{ __('documents.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($requests as $req)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $req->student_name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $req->document_type_code }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $req->institution_name_ar }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $req->submitted_at ? \Carbon\Carbon::parse($req->submitted_at)->format('Y-m-d') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.documents.approve', $req->id) }}"
                                   class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                                    {{ __('documents.review_and_approve') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
