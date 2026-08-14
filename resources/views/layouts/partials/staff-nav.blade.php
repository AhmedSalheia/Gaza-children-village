{{-- F23 — Staff portal navigation links --}}
<ul class="portal-nav" role="list">
    <li class="portal-nav__item">
        <a href="{{ url('/staff') }}" class="portal-nav__link">
            {{ __('ui.dashboard') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/staff/profile') }}" class="portal-nav__link">
            {{ __('ui.staff') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/staff/calendar') }}" class="portal-nav__link">
            {{ __('ui.calendar') }}
        </a>
    </li>
</ul>
