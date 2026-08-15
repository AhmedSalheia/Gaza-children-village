{{-- Admin portal navigation — each link is gated by the required permission --}}
{{-- $navCan: Closure(string $key): bool — passed from layouts/admin.blade.php --}}
<ul class="portal-nav" role="list">
    {{-- Dashboard is visible to every authenticated admin --}}
    <li class="portal-nav__item">
        <a href="{{ route('admin.dashboard') }}" class="portal-nav__link @active('admin/dashboard')">
            {{ __('ui.dashboard', [], null, 'Dashboard') }}
        </a>
    </li>

    @if($navCan('student.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.students.index') }}" class="portal-nav__link @active('admin/students*')">
            {{ __('ui.students', [], null, 'Students') }}
        </a>
    </li>
    @endif

    @if($navCan('guardian_relationship.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.guardians.index') }}" class="portal-nav__link @active('admin/guardians*') @active('admin/relationships*')">
            {{ __('ui.guardians', [], null, 'Guardians') }}
        </a>
    </li>
    @endif

    @if($navCan('enrollment.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.enrollments.index') }}" class="portal-nav__link @active('admin/enrollment*') @active('admin/transfers*') @active('admin/promotions*')">
            {{ __('ui.enrollments', [], null, 'Enrolments') }}
        </a>
    </li>
    @endif

    @if($navCan('institution.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.institutions.index') }}" class="portal-nav__link @active('admin/institutions*')">
            {{ __('ui.institutions', [], null, 'Institutions') }}
        </a>
    </li>
    @endif

    @if($navCan('academic_year.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.calendar.index') }}" class="portal-nav__link @active('admin/calendar*')">
            {{ __('ui.calendar', [], null, 'Calendar') }}
        </a>
    </li>
    @endif

    @if($navCan('import.upload'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.imports.index') }}" class="portal-nav__link @active('admin/imports*')">
            {{ __('ui.imports', [], null, 'Imports') }}
        </a>
    </li>
    @endif

    @if($navCan('staff_profile.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.staff.index') }}" class="portal-nav__link @active('admin/staff*')">
            {{ __('ui.staff', [], null, 'Staff') }}
        </a>
    </li>
    @endif

    @if($navCan('account.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.accounts.index') }}" class="portal-nav__link @active('admin/accounts*')">
            {{ __('ui.accounts', [], null, 'Accounts') }}
        </a>
    </li>
    @endif

    @if($navCan('teaching_assignment.manage'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.assignments.teaching') }}" class="portal-nav__link @active('admin/assignments/teaching')">
            {{ __('ui.teaching_assignments', [], null, 'Teaching') }}
        </a>
    </li>
    @endif

    @if($navCan('homeroom_assignment.manage'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.assignments.homeroom') }}" class="portal-nav__link @active('admin/assignments/homeroom')">
            {{ __('ui.homeroom_assignments', [], null, 'Homeroom') }}
        </a>
    </li>
    @endif

    @if($navCan('grading_scale.manage'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.marks.grading-scales') }}" class="portal-nav__link @active('admin/marks*')">
            {{ __('ui.marks', [], null, 'Marks') }}
        </a>
    </li>
    @endif

    @if($navCan('results.publish') || $navCan('student_attendance.publish'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.publications.results') }}" class="portal-nav__link @active('admin/publications*')">
            {{ __('ui.publications', [], null, 'Publications') }}
        </a>
    </li>
    @endif

    @if($navCan('attendance_report.read') || $navCan('result_report.read'))
    <li class="portal-nav__item">
        <a href="{{ $navCan('attendance_report.read') ? route('admin.reports.attendance') : route('admin.reports.marks') }}"
           class="portal-nav__link @active('admin/reports*')">
            {{ __('ui.reports', [], null, 'Reports') }}
        </a>
    </li>
    @endif

    @if($navCan('audit.view'))
    <li class="portal-nav__item">
        <a href="{{ route('admin.audit.civil-registry') }}" class="portal-nav__link @active('admin/audit*')">
            {{ __('ui.audit', [], null, 'Audit') }}
        </a>
    </li>
    @endif
</ul>
