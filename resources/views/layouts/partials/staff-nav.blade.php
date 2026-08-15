{{-- Staff portal navigation — each link gated by the required permission --}}
{{-- $navCan: Closure(string): bool — computed in layouts/staff.blade.php --}}
<ul class="portal-nav" role="list">
    {{-- Dashboard visible to every authenticated staff member --}}
    <li class="portal-nav__item">
        <a href="{{ route('staff.dashboard') }}" class="portal-nav__link @active('staff/dashboard')">
            {{ __('ui.dashboard', [], null, 'Dashboard') }}
        </a>
    </li>

    @if($navCan('student.view') || $navCan('student.view_restricted'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.students.index') }}" class="portal-nav__link @active('staff/students*')">
            {{ __('ui.students', [], null, 'Students') }}
        </a>
    </li>
    @endif

    {{-- Class lists visible to teachers and above (enrollment.view) --}}
    @if($navCan('enrollment.view'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.class-lists.index') }}" class="portal-nav__link @active('staff/class-lists*')">
            {{ __('ui.class_lists', [], null, 'Class Lists') }}
        </a>
    </li>
    @endif

    @if($navCan('enrollment.manage'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.enrollments.index') }}" class="portal-nav__link @active('staff/enrollments*')">
            {{ __('ui.enrollments', [], null, 'Enrolments') }}
        </a>
    </li>
    @endif

    {{-- Promotions visible to secretaries (enrollment.manage) and principals (enrollment.promote) --}}
    @if($navCan('enrollment.manage') || $navCan('enrollment.promote'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.promotions.index') }}" class="portal-nav__link @active('staff/promotions*')">
            {{ __('ui.promotions', [], null, 'Promotions') }}
        </a>
    </li>
    @endif

    @if($navCan('import.upload'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.imports.index') }}" class="portal-nav__link @active('staff/imports*')">
            {{ __('ui.imports', [], null, 'Imports') }}
        </a>
    </li>
    @endif
</ul>
