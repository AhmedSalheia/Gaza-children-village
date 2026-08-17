<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ __('documents.request_detail_title') }}</h1>
        <a href="{{ route('guardian.documents.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← {{ __('documents.back_to_list') }}</a>
    </div>

    @if ($flashMessage)
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-green-700 text-sm">{{ $flashMessage }}</p>
        </div>
    @endif

    @if (! empty($errors))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
            @foreach ($errors as $error)
                <p class="text-red-700 text-sm">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4">
        <dl class="grid gap-3 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">{{ __('documents.student') }}</dt>
                <dd class="font-medium text-gray-900">{{ $studentName ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">{{ __('documents.document_type') }}</dt>
                <dd class="font-medium text-gray-900">{{ $request->document_type_code }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">{{ __('documents.locale_field') }}</dt>
                <dd class="font-medium text-gray-900">{{ $request->locale === 'ar' ? __('documents.locale_ar') : __('documents.locale_en') }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">{{ __('documents.status') }}</dt>
                <dd>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $request->status === 'issued' ? 'bg-green-100 text-green-800' : '' }}
                        {{ in_array($request->status, ['rejected','cancelled','generation_failed']) ? 'bg-red-100 text-red-800' : '' }}
                        {{ in_array($request->status, ['submitted','pending_completeness','completeness_passed','awaiting_approval','approved','generating']) ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $request->status === 'pending_clarification' ? 'bg-orange-100 text-orange-800' : '' }}
                    ">
                        {{ $request->status }}
                    </span>
                </dd>
            </div>
            @if ($request->purpose_notes)
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">{{ __('documents.notes') }}</dt>
                    <dd class="text-gray-900">{{ $request->purpose_notes }}</dd>
                </div>
            @endif
            @if ($request->clarification_reason)
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 mb-1">{{ __('documents.clarification_request') }}</dt>
                    <dd class="text-orange-700 bg-orange-50 rounded p-3 text-sm">{{ $request->clarification_reason }}</dd>
                </div>
            @endif
            @if ($request->rejection_reason)
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 mb-1">{{ __('documents.rejection_reason') }}</dt>
                    <dd class="text-red-700 bg-red-50 rounded p-3 text-sm">{{ $request->rejection_reason }}</dd>
                </div>
            @endif
            <div class="flex justify-between py-2">
                <dt class="text-gray-500">{{ __('documents.request_date') }}</dt>
                <dd class="text-gray-700">{{ \Carbon\Carbon::parse($request->created_at)->format('Y-m-d') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Issued document download --}}
    @if ($issuedDoc)
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-4">
            <h2 class="font-semibold text-green-800 mb-3">✓ {{ __('documents.document_ready') }}</h2>
            <p class="text-sm text-green-700 mb-1">{{ __('documents.document_number_label') }} <strong>{{ $issuedDoc->document_number }}</strong></p>
            <p class="text-sm text-green-700 mb-4">{{ __('documents.issued_date_label') }} {{ \Carbon\Carbon::parse($issuedDoc->issued_at)->format('Y-m-d') }}</p>
            <a href="{{ route('guardian.documents.download', $issuedDoc->id) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                {{ __('documents.download_pdf') }}
            </a>
        </div>
    @endif

    {{-- Clarification response form --}}
    @if ($request->status === 'pending_clarification')
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-6 mb-4">
            <h2 class="font-semibold text-orange-800 mb-3">{{ __('documents.respond_clarification_title') }}</h2>
            <textarea wire:model="clarificationResponse" rows="4" placeholder="{{ __('documents.clarification_response_placeholder') }}"
                      class="w-full border border-orange-300 rounded-lg px-3 py-2 text-sm resize-none mb-3"></textarea>
            <button wire:click="provideClarification"
                    class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">
                {{ __('documents.send_response') }}
            </button>
        </div>
    @endif

    {{-- Cancel button --}}
    @if (! $request->isTerminal())
        <div class="flex justify-end">
            <button wire:click="cancel"
                    wire:confirm="{{ __('documents.cancel_confirm') }}"
                    class="px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                {{ __('documents.cancel_request') }}
            </button>
        </div>
    @endif
</div>
