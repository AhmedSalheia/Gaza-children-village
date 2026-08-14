{{--
    F20 / F23 — Locale switcher component.
    Renders two toggle buttons: Arabic and English.
    POST to /locale-switch with CSRF token.
    Active locale button carries aria-pressed="true" and a visual indicator.
--}}
<div class="locale-switcher" role="group" aria-label="{{ __('ui.current_locale') }}">
    @foreach(['ar' => __('ui.switch_to_arabic'), 'en' => __('ui.switch_to_english')] as $code => $label)
        @if(app()->getLocale() === $code)
            <span
                class="locale-switcher__btn locale-switcher__btn--active"
                aria-pressed="true"
                aria-current="true"
                lang="{{ $code }}"
                dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
            >{{ $label }}</span>
        @else
            <form method="POST" action="{{ route('locale.switch') }}" class="locale-switcher__form">
                @csrf
                <input type="hidden" name="locale" value="{{ $code }}">
                <button
                    type="submit"
                    class="locale-switcher__btn"
                    lang="{{ $code }}"
                    dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                    aria-pressed="false"
                >{{ $label }}</button>
            </form>
        @endif
    @endforeach
</div>
