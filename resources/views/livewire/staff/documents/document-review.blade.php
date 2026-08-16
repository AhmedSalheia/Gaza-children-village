<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">مراجعة طلب الوثيقة</h1>
        <a href="{{ route('staff.documents.queue') }}" class="text-sm text-gray-500 hover:text-gray-700">← العودة للقائمة</a>
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
                <dt class="text-gray-500">الطالب</dt>
                <dd class="font-medium text-gray-900">{{ $studentName ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">نوع الوثيقة</dt>
                <dd class="text-gray-900">{{ $request->document_type_code }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">الحالة</dt>
                <dd>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $request->status === 'submitted' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $request->status === 'completeness_passed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $request->status === 'completeness_failed' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $request->status === 'pending_completeness' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $request->status === 'awaiting_approval' ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $request->status === 'pending_clarification' ? 'bg-orange-100 text-orange-800' : '' }}
                    ">
                        {{ $request->status }}
                    </span>
                </dd>
            </div>
            @if ($request->purpose_notes)
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 mb-1">ملاحظات ولي الأمر</dt>
                    <dd class="text-gray-900 bg-gray-50 rounded p-3">{{ $request->purpose_notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Completeness failures --}}
    @if (! empty($completenessFailures))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4">
            <h3 class="font-semibold text-red-800 mb-2">نتائج فحص الاكتمال — بيانات ناقصة:</h3>
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach ($completenessFailures as $failure)
                    <li>{{ $failure }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Actions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">الإجراءات المتاحة</h2>

        <div class="grid gap-4">
            @if (in_array($request->status, ['submitted', 'completeness_failed']))
                <button wire:click="runCompletenessCheck"
                        wire:loading.attr="disabled"
                        class="px-4 py-3 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 text-right">
                    <strong>فحص اكتمال البيانات</strong>
                    <p class="text-indigo-200 text-xs mt-0.5">يفحص أن بيانات الطالب مكتملة لإنتاج الوثيقة</p>
                </button>
            @endif

            @if ($request->status === 'completeness_passed')
                <button wire:click="forwardForApproval"
                        class="px-4 py-3 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 text-right">
                    <strong>إحالة للمدير للموافقة</strong>
                    <p class="text-green-200 text-xs mt-0.5">يحول الطلب إلى المدير لاتخاذ قرار الموافقة</p>
                </button>
            @endif

            @if (in_array($request->status, ['submitted', 'pending_completeness', 'completeness_failed']))
                <div class="border border-orange-200 rounded-lg p-4 bg-orange-50">
                    <h3 class="font-medium text-orange-800 mb-2 text-sm">طلب توضيح من ولي الأمر</h3>
                    <textarea wire:model="clarificationReason" rows="3" placeholder="اكتب سبب طلب التوضيح..."
                              class="w-full border border-orange-300 rounded-lg px-3 py-2 text-sm resize-none mb-2 bg-white"></textarea>
                    <button wire:click="requestClarification"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">
                        إرسال طلب التوضيح
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
