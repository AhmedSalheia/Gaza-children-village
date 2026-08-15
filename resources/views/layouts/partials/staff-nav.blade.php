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

    {{-- Assignment overview visible to full-scope positions only (principal/deputy) --}}
    @if($navCan('teaching_assignment.manage'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.assignments.index') }}" class="portal-nav__link @active('staff/assignments*')">
            {{ __('ui.assignments', [], null, 'Assignments') }}
        </a>
    </li>
    @endif

    {{-- Attendance: teacher daily entry --}}
    @if($navCan('student_attendance.enter'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.attendance.index') }}" class="portal-nav__link @active('staff/attendance') @active('staff/attendance/sheet*')">
            {{ __('ui.attendance', [], null, 'Attendance') }}
        </a>
    </li>
    @endif

    {{-- Attendance: secretary review queue --}}
    @if($navCan('student_attendance.return'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.attendance.queue') }}" class="portal-nav__link @active('staff/attendance/queue*') @active('staff/attendance/verify*')">
            {{ __('ui.attendance_queue', [], null, 'Attendance Queue') }}
        </a>
    </li>
    @endif

    {{-- Staff Attendance: secretary daily entry --}}
    @if($navCan('staff_attendance.enter'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.staff-attendance.index') }}" class="portal-nav__link @active('staff/staff-attendance') @active('staff/staff-attendance/*')">
            {{ __('ui.staff_attendance', [], null, 'Staff Attendance') }}
        </a>
    </li>
    @endif

    {{-- Staff Attendance: QR scan review (secretary/deputy) --}}
    @if($navCan('attendance_scan.review'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.staff-attendance.scan-queue') }}" class="portal-nav__link @active('staff/staff-attendance/scan-queue*')">
            {{ __('ui.scan_queue', [], null, 'Scan Queue') }}
        </a>
    </li>
    @endif

    {{-- Staff Attendance: dashboard (principal/deputy) --}}
    @if($navCan('staff_attendance.read') and !$navCan('staff_attendance.enter'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.staff-attendance.dashboard') }}" class="portal-nav__link @active('staff/staff-attendance/dashboard*')">
            {{ __('ui.staff_attendance_dashboard', [], null, 'Staff Attendance') }}
        </a>
    </li>
    @endif

    {{-- Marks: teacher entry --}}
    @if($navCan('marks.enter') || $navCan('marks.read'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.marks.index') }}" class="portal-nav__link @active('staff/marks') @active('staff/marks/sheet*')">
            {{ __('ui.marks', [], null, 'Marks') }}
        </a>
    </li>
    @endif

    {{-- Marks: secretary verification queue --}}
    @if($navCan('marks.verify'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.marks.review') }}" class="portal-nav__link @active('staff/marks/review*')">
            {{ __('ui.marks_review', [], null, 'Marks Review') }}
        </a>
    </li>
    @endif

    {{-- Marks: principal window extension --}}
    @if($navCan('mark_window.extend'))
    <li class="portal-nav__item">
        <a href="{{ route('staff.marks.windows') }}" class="portal-nav__link @active('staff/marks/windows*')">
            {{ __('ui.mark_windows', [], null, 'Mark Windows') }}
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
