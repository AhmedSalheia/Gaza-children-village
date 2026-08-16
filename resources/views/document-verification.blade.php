<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.document_verification', [], null, 'Document Verification') }} — GCV</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .card { background: white; border-radius: 1rem; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 2.5rem; max-width: 520px; width: 100%; }
        .logo { text-align: center; margin-bottom: 1.5rem; font-size: 1.1rem; font-weight: 700; color: #1e3a5f; letter-spacing: .02em; }
        .badge { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem 1.1rem; border-radius: 9999px; font-weight: 600; font-size: .95rem; margin-bottom: 1.2rem; }
        .badge-valid { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-invalid { background: #fef3c7; color: #92400e; }
        .badge-rate_limited { background: #f1f5f9; color: #475569; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; }
        .fields { margin-top: 1.2rem; display: grid; gap: .75rem; }
        .field { display: flex; flex-direction: column; gap: .2rem; }
        .field-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
        .field-value { font-size: 1rem; color: #1e293b; }
        .disclaimer { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: .8rem; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">GCV — مجتمع غزة التطوعي</div>

    @if ($status === 'valid')
        <div class="badge badge-valid">✓ وثيقة صحيحة / Valid Document</div>
        <h1>تحقق الوثيقة — Document Verification</h1>
        <div class="fields">
            <div class="field">
                <div class="field-label">رقم الوثيقة / Document Number</div>
                <div class="field-value">{{ $document['document_number'] }}</div>
            </div>
            <div class="field">
                <div class="field-label">نوع الوثيقة / Document Type</div>
                <div class="field-value">{{ $document['document_type'] }}</div>
            </div>
            <div class="field">
                <div class="field-label">المدرسة / Institution</div>
                <div class="field-value">{{ $document['institution_name_ar'] }} / {{ $document['institution_name_en'] }}</div>
            </div>
            <div class="field">
                <div class="field-label">تاريخ الإصدار / Issue Date</div>
                <div class="field-value">{{ $document['issued_at'] }}</div>
            </div>
        </div>

    @elseif ($status === 'cancelled')
        <div class="badge badge-cancelled">✗ وثيقة ملغاة / Cancelled</div>
        <h1>تحقق الوثيقة — Document Verification</h1>
        <div class="fields">
            <div class="field">
                <div class="field-label">رقم الوثيقة / Document Number</div>
                <div class="field-value">{{ $document['document_number'] }}</div>
            </div>
            <div class="field">
                <div class="field-label">نوع الوثيقة / Document Type</div>
                <div class="field-value">{{ $document['document_type'] }}</div>
            </div>
            <div class="field">
                <div class="field-label">المدرسة / Institution</div>
                <div class="field-value">{{ $document['institution_name_ar'] }} / {{ $document['institution_name_en'] }}</div>
            </div>
            <div class="field">
                <div class="field-label">تاريخ الإصدار / Issue Date</div>
                <div class="field-value">{{ $document['issued_at'] }}</div>
            </div>
            <div class="field" style="background:#fee2e2;padding:.75rem;border-radius:.5rem;">
                <div class="field-label">الحالة / Status</div>
                <div class="field-value" style="color:#991b1b;">هذه الوثيقة تم إلغاؤها / This document has been cancelled</div>
            </div>
        </div>

    @elseif ($status === 'rate_limited')
        <div class="badge badge-rate_limited">⏱ تجاوز الحد / Rate Limited</div>
        <h1>تجاوزت الحد المسموح</h1>
        <p style="margin-top:.75rem;color:#64748b;">يرجى المحاولة مجدداً بعد دقيقة. / Please try again after one minute.</p>

    @else
        <div class="badge badge-invalid">? غير معرّفة / Not Found</div>
        <h1>تحقق الوثيقة — Document Verification</h1>
        <p style="margin-top:.75rem;color:#64748b;">
            لم يتم العثور على وثيقة مرتبطة بهذا الرمز.
            يرجى التأكد من أن الرمز صحيح.<br><br>
            No document was found for this verification code.
            Please ensure the code is correct.
        </p>
    @endif

    <div class="disclaimer">
        لا تظهر هذه الصفحة أي بيانات شخصية للطلاب. /
        This page does not reveal any student personal information.
    </div>
</div>
</body>
</html>
