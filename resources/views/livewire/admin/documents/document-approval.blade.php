<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">موافقة على طلب الوثيقة</h1>
        <a href="{{ route('admin.documents.queue') }}" class="text-sm text-gray-500 hover:text-gray-700">← العودة للقائمة</a>
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

    @if ($templateHashWarning)
        <div class="mb-4 bg-amber-50 border border-amber-300 rounded-xl p-4">
            <h3 class="font-semibold text-amber-800 flex items-center gap-2">⚠ تنبيه بشأن قالب الوثيقة</h3>
            <p class="text-amber-700 text-sm mt-1">{{ $templateHashWarning }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <dl class="grid gap-3 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">الطالب</dt>
                <dd class="font-medium text-gray-900">{{ $studentName ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">المدرسة</dt>
                <dd class="text-gray-900">{{ $institutionName ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">نوع الوثيقة</dt>
                <dd class="text-gray-900">{{ $request->document_type_code }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">اللغة</dt>
                <dd class="text-gray-900">{{ $request->locale === 'ar' ? 'العربية' : 'English' }}</dd>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <dt class="text-gray-500">الحالة</dt>
                <dd>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ $request->status }}
                    </span>
                </dd>
            </div>
            @if ($request->purpose_notes)
                <div class="py-2">
                    <dt class="text-gray-500 mb-1">ملاحظات ولي الأمر</dt>
                    <dd class="text-gray-900 bg-gray-50 rounded p-3">{{ $request->purpose_notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    @if ($request->status === 'awaiting_approval')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Approve --}}
            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <h2 class="font-semibold text-green-800 mb-3">الموافقة على الطلب</h2>
                <p class="text-green-700 text-sm mb-4">بالموافقة، سيتم توليد وثيقة PDF وإتاحتها للتحميل.</p>
                <button wire:click="approve"
                        wire:confirm="هل أنت متأكد من الموافقة على هذا الطلب؟"
                        wire:loading.attr="disabled"
                        class="w-full px-4 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50">
                    <span wire:loading.remove>✓ الموافقة وإصدار الوثيقة</span>
                    <span wire:loading>جاري المعالجة...</span>
                </button>
            </div>

            {{-- Reject --}}
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <h2 class="font-semibold text-red-800 mb-3">رفض الطلب</h2>
                <textarea wire:model="rejectionReason" rows="3" placeholder="سبب الرفض (مطلوب)..."
                          class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm resize-none mb-3 bg-white"></textarea>
                <button wire:click="reject"
                        wire:confirm="هل أنت متأكد من رفض هذا الطلب؟"
                        class="w-full px-4 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">
                    ✗ رفض الطلب
                </button>
            </div>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-gray-500">
            <p>تم اتخاذ الإجراء على هذا الطلب. الحالة الحالية: <strong>{{ $request->status }}</strong></p>
        </div>
    @endif
</div>
