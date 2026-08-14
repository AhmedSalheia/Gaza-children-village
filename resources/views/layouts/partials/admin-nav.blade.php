{{-- F23 — Admin portal navigation links --}}
<ul class="portal-nav" role="list">
    <li class="portal-nav__item">
        <a href="{{ url('/admin') }}" class="portal-nav__link @active('admin')">
            {{ __('ui.dashboard') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/admin/institutions') }}" class="portal-nav__link @active('admin/institutions*')">
            {{ __('ui.institutions') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/admin/staff') }}" class="portal-nav__link @active('admin/staff*')">
            {{ __('ui.staff') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/admin/calendar') }}" class="portal-nav__link @active('admin/calendar*')">
            {{ __('ui.calendar') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/admin/accounts') }}" class="portal-nav__link @active('admin/accounts*')">
            {{ __('ui.accounts') }}
        </a>
    </li>
    <li class="portal-nav__item">
        <a href="{{ url('/admin/audit') }}" class="portal-nav__link @active('admin/audit*')">
            {{ __('ui.audit') }}
        </a>
    </li>
</ul>
