<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LoginController;
use App\Livewire\Admin\AcademicStructure\AcademicLevelIndex;
use App\Livewire\Admin\AcademicStructure\ClassGroupIndex;
use App\Livewire\Admin\AcademicStructure\ClassroomIndex;
use App\Livewire\Admin\AcademicStructure\SubjectIndex;
use App\Livewire\Admin\Assignments\HomeroomAssignmentIndex;
use App\Livewire\Admin\Assignments\TeachingAssignmentIndex;
use App\Livewire\Admin\Audit\CivilRegistryAudit;
use App\Livewire\Admin\Marks\AssessmentDefinitionIndex;
use App\Livewire\Admin\Marks\GradingScaleIndex;
use App\Livewire\Admin\Marks\MarkEntryWindowIndex;
use App\Livewire\Admin\Marks\MarkSheetOverview;
use App\Livewire\Admin\Publications\AttendancePublicationPolicyConfig;
use App\Livewire\Admin\Publications\ResultPublicationManager;
use App\Livewire\Admin\Calendar\CalendarIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Enrollments\EnrollmentIndex;
use App\Livewire\Admin\Enrollments\PromotionIndex;
use App\Livewire\Admin\Enrollments\TransferIndex;
use App\Livewire\Admin\Imports\ImportBatchDetail;
use App\Livewire\Admin\Imports\ImportBatchIndex;
use App\Livewire\Admin\Institutions\InstitutionIndex;
use App\Livewire\Admin\People\PeopleIndex;
use App\Livewire\Admin\Stubs\ComingSoonPage;
use App\Livewire\Admin\Students\AddStudent;
use App\Livewire\Admin\Students\GuardianDetail;
use App\Livewire\Admin\Students\GuardianIndex;
use App\Livewire\Admin\Students\RelationshipIndex;
use App\Livewire\Admin\Students\StudentDetail;
use App\Livewire\Admin\Students\StudentIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /admin and named admin.*.
|
| Protected routes require the 'admin' guard (AdministrativeAccount only).
| Admin sessions are anonymous in the staff and guardian portals.
|
| The portal.version:admin middleware compares the session-stored auth_version
| against the account's current value on every protected request, enabling
| server-side session revocation via RevokePortalAccountSessions.
|
*/

Route::prefix('admin')->name('admin.')->group(function (): void {

    // ── Login / logout (F10) ─────────────────────────────────────────────

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ── Protected routes ─────────────────────────────────────────────────

    Route::middleware(['auth:admin', 'portal.version:admin'])->group(function (): void {

        // Dashboard
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        // Institutions
        Route::get('/institutions', InstitutionIndex::class)->name('institutions.index');

        // Calendar
        Route::get('/calendar', CalendarIndex::class)->name('calendar.index');

        // Academic structure
        Route::get('/academic/levels', AcademicLevelIndex::class)->name('academic.levels');
        Route::get('/academic/classrooms', ClassroomIndex::class)->name('academic.classrooms');
        Route::get('/academic/class-groups', ClassGroupIndex::class)->name('academic.class-groups');
        Route::get('/academic/subjects', SubjectIndex::class)->name('academic.subjects');

        // Assignments
        Route::get('/assignments/teaching', TeachingAssignmentIndex::class)->name('assignments.teaching');
        Route::get('/assignments/homeroom', HomeroomAssignmentIndex::class)->name('assignments.homeroom');

        // People
        Route::get('/people', PeopleIndex::class)->name('people.index');

        // Students
        Route::get('/students', StudentIndex::class)->name('students.index');
        Route::get('/students/add', AddStudent::class)->name('students.add');
        Route::get('/students/{studentId}', StudentDetail::class)->name('students.detail');

        // Guardians
        Route::get('/guardians', GuardianIndex::class)->name('guardians.index');
        Route::get('/guardians/{guardianId}', GuardianDetail::class)->name('guardians.detail');

        // Relationships
        Route::get('/relationships', RelationshipIndex::class)->name('relationships.index');

        // Enrollments
        Route::get('/enrollments', EnrollmentIndex::class)->name('enrollments.index');
        Route::get('/transfers', TransferIndex::class)->name('transfers.index');
        Route::get('/promotions', PromotionIndex::class)->name('promotions.index');

        // Imports
        Route::get('/imports', ImportBatchIndex::class)->name('imports.index');
        Route::get('/imports/{batchId}', ImportBatchDetail::class)->name('imports.detail');

        // Audit
        Route::get('/audit/civil-registry', CivilRegistryAudit::class)->name('audit.civil-registry');

        // Marks: grading scales, assessment definitions, windows, overview
        Route::get('/marks/grading-scales', GradingScaleIndex::class)->name('marks.grading-scales');
        Route::get('/marks/assessments', AssessmentDefinitionIndex::class)->name('marks.assessments');
        Route::get('/marks/windows', MarkEntryWindowIndex::class)->name('marks.windows');
        Route::get('/marks/overview', MarkSheetOverview::class)->name('marks.overview');

        // Publications: result publication manager, attendance publication policy
        Route::get('/publications/results', ResultPublicationManager::class)->name('publications.results');
        Route::get('/publications/attendance', AttendancePublicationPolicyConfig::class)->name('publications.attendance');

        // Stub pages — full implementation deferred to Full Admin Portal release
        Route::get('/staff', ComingSoonPage::class)->name('staff.index');
        Route::get('/accounts', ComingSoonPage::class)->name('accounts.index');
        Route::get('/roles', ComingSoonPage::class)->name('roles.index');
        Route::get('/audit', ComingSoonPage::class)->name('audit.index');
    });

    // ── Unprotected smoke-test placeholder ────────────────────────────────
    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
