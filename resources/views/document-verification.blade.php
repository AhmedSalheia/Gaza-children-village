<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.document_verification', [], null, 'Document Verification') }} — GCV DATA</title>
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="verify-page">
    <div class="verify-card">
        <div class="verify-card__head">
            <div class="brand-mark">
                <span class="brand-mark__glyph" aria-hidden="true">GCV</span>
                <span class="brand-mark__text">
                    <span class="brand-mark__name">GCV DATA</span>
                    <span class="brand-mark__tagline">مجتمع غزة التطوعي — Gaza Community Volunteers</span>
                </span>
            </div>
        </div>

        <div class="verify-card__body">
            @if ($status === 'valid')
                <div class="verify-status verify-status--valid">✓ وثيقة صحيحة / Valid Document</div>
                <h1 style="font-size: var(--text-xl); margin: 0;">تحقق الوثيقة — Document Verification</h1>
                <div class="verify-fields">
                    <div class="verify-field">
                        <div class="verify-field__label">رقم الوثيقة / Document Number</div>
                        <div class="verify-field__value">{{ $document['document_number'] }}</div>
                    </div>
                    <div class="verify-field">
                        <div class="verify-field__label">نوع الوثيقة / Document Type</div>
                        <div class="verify-field__value">{{ $document['document_type'] }}</div>
                    </div>
                    <div class="verify-field">
                        <div class="verify-field__label">المدرسة / Institution</div>
                        <div class="verify-field__value">{{ $document['institution_name_ar'] }} / {{ $document['institution_name_en'] }}</div>
                    </div>
                    <div class="verify-field">
                        <div class="verify-field__label">تاريخ الإصدار / Issue Date</div>
                        <div class="verify-field__value">{{ $document['issued_at'] }}</div>
                    </div>
                </div>

            @elseif ($status === 'cancelled')
                <div class="verify-status verify-status--cancelled">✗ وثيقة ملغاة / Cancelled</div>
                <h1 style="font-size: var(--text-xl); margin: 0;">تحقق الوثيقة — Document Verification</h1>
                <div class="verify-fields">
                    <div class="verify-field">
                        <div class="verify-field__label">رقم الوثيقة / Document Number</div>
                        <div class="verify-field__value">{{ $document['document_number'] }}</div>
                    </div>
                    <div class="verify-field">
                        <div class="verify-field__label">نوع الوثيقة / Document Type</div>
                        <div class="verify-field__value">{{ $document['document_type'] }}</div>
                    </div>
                    <div class="verify-field">
                        <div class="verify-field__label">المدرسة / Institution</div>
                        <div class="verify-field__value">{{ $document['institution_name_ar'] }} / {{ $document['institution_name_en'] }}</div>
                    </div>
                    <div class="verify-field">
                        <div class="verify-field__label">تاريخ الإصدار / Issue Date</div>
                        <div class="verify-field__value">{{ $document['issued_at'] }}</div>
                    </div>
                    <div class="verify-field verify-field--danger">
                        <div class="verify-field__label">الحالة / Status</div>
                        <div class="verify-field__value">هذه الوثيقة تم إلغاؤها / This document has been cancelled</div>
                    </div>
                </div>

            @elseif ($status === 'rate_limited')
                <div class="verify-status verify-status--muted">⏱ تجاوز الحد / Rate Limited</div>
                <h1 style="font-size: var(--text-xl); margin: 0;">تجاوزت الحد المسموح</h1>
                <p style="margin-block-start: var(--space-3); color: var(--text-secondary);">يرجى المحاولة مجدداً بعد دقيقة. / Please try again after one minute.</p>

            @else
                <div class="verify-status verify-status--invalid">? غير معرّفة / Not Found</div>
                <h1 style="font-size: var(--text-xl); margin: 0;">تحقق الوثيقة — Document Verification</h1>
                <p style="margin-block-start: var(--space-3); color: var(--text-secondary);">
                    لم يتم العثور على وثيقة مرتبطة بهذا الرمز.
                    يرجى التأكد من أن الرمز صحيح.<br><br>
                    No document was found for this verification code.
                    Please ensure the code is correct.
                </p>
            @endif

            <div class="verify-disclaimer">
                لا تظهر هذه الصفحة أي بيانات شخصية للطلاب. /
                This page does not reveal any student personal information.
            </div>
        </div>
    </div>
</div>
</body>
</html>
