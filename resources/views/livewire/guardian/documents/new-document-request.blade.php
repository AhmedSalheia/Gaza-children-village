<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">{{ __('ui.new_document_request', [], null, 'New Document Request') }}</h1>
    </div>

    @if ($submitted)
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
            <div class="text-3xl mb-2">✓</div>
            <p class="font-semibold text-green-800">{{ __('ui.request_submitted', [], null, 'Your request has been submitted.') }}</p>
            <p class="text-green-600 text-sm mt-1">{{ __('ui.request_submitted_detail', [], null, 'The school secretary will review it shortly.') }}</p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('guardian.documents.detail', $createdRequestId) }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                    {{ __('ui.view_request', [], null, 'View Request') }}
                </a>
                <a href="{{ route('guardian.documents.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                    {{ __('ui.all_requests', [], null, 'All Requests') }}
                </a>
            </div>
        </div>
    @else
        @if (! empty($errors))
            <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
                @foreach ($errors as $error)
                    <p class="text-red-700 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Step indicator --}}
        <div class="mb-6 flex gap-2 text-sm">
            @foreach ([1 => __('documents.step_select_student'), 2 => __('documents.step_document_type'), 3 => __('documents.step_review')] as $n => $label)
                <div class="flex items-center gap-1 {{ $step >= $n ? 'text-indigo-700 font-semibold' : 'text-gray-400' }}">
                    <span class="w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs
                        {{ $step > $n ? 'bg-indigo-600 border-indigo-600 text-white' : ($step === $n ? 'border-indigo-600 text-indigo-600' : 'border-gray-300 text-gray-400') }}">
                        {{ $step > $n ? '✓' : $n }}
                    </span>
                    <span>{{ $label }}</span>
                </div>
                @if ($n < 3) <div class="flex-1 h-0.5 mt-3 {{ $step > $n ? 'bg-indigo-600' : 'bg-gray-200' }}"></div> @endif
            @endforeach
        </div>

        {{-- Step 1: Select Student --}}
        @if ($step === 1)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">{{ __('documents.step_select_student') }}</h2>
                @if ($students->isEmpty())
                    <p class="text-gray-500">{{ __('documents.no_linked_students') }}</p>
                @else
                    <div class="grid gap-3">
                        @foreach ($students as $student)
                            <button wire:click="selectStudent({{ $student->id }})"
                                    class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-indigo-400 hover:bg-indigo-50 transition-colors text-right">
                                <span class="font-medium text-gray-900">{{ $student->full_name_ar }}</span>
                                <span class="text-indigo-600">{{ __('documents.select_action') }} ←</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Step 2: Document type --}}
        @if ($step === 2)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-800 mb-1">{{ __('documents.step_document_type') }}</h2>
                @if ($selectedStudent)
                    <p class="text-sm text-gray-500 mb-4">{{ __('documents.student') }}: {{ $selectedStudent->full_name_ar }}</p>
                @endif

                <div class="grid gap-3 mb-4">
                    @foreach ($documentTypes as $type)
                        <label class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer
                                    {{ $documentTypeCode === $type->code ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="documentTypeCode" value="{{ $type->code }}" class="mt-1">
                            <div>
                                <div class="font-medium text-gray-900">{{ $type->label_ar ?? $type->code }}</div>
                                @if ($type->label_en ?? false)
                                    <div class="text-xs text-gray-500">{{ $type->label_en }}</div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.locale_field') }}</label>
                    <select wire:model="locale" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="ar">{{ __('documents.locale_ar') }}</option>
                        <option value="en">{{ __('documents.locale_en') }}</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.notes_optional') }}</label>
                    <textarea wire:model="purposeNotes" rows="3" placeholder="{{ __('documents.notes_placeholder') }}"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                </div>

                <div class="flex justify-between">
                    <button wire:click="backToStep(1)" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">← {{ __('documents.back') }}</button>
                    <button wire:click="proceedToReview" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                        {{ __('documents.review_request') }} →
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 3: Review and confirm --}}
        @if ($step === 3)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">{{ __('documents.review_request') }}</h2>

                <dl class="grid gap-3 text-sm">
                    @if ($selectedStudent)
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <dt class="text-gray-500">{{ __('documents.student') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $selectedStudent->full_name_ar }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <dt class="text-gray-500">{{ __('documents.document_type') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $documentTypeCode }}</dd>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <dt class="text-gray-500">{{ __('documents.locale_field') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $locale === 'ar' ? __('documents.locale_ar') : __('documents.locale_en') }}</dd>
                    </div>
                    @if ($purposeNotes)
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <dt class="text-gray-500">{{ __('documents.notes') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $purposeNotes }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 flex justify-between">
                    <button wire:click="backToStep(2)" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">← {{ __('documents.edit') }}</button>
                    <button wire:click="submit" wire:loading.attr="disabled"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove>{{ __('documents.submit_request') }}</span>
                        <span wire:loading>{{ __('documents.submitting') }}</span>
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>
