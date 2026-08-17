<?php

declare(strict_types=1);

use App\Http\Controllers\Staff\IssuedDocumentDownloadController;
use App\Http\Controllers\Staff\LoginController;
use App\Http\Controllers\Staff\ReportDownloadController;
use App\Http\Controllers\Staff\SecureAttachmentDownloadController;
use App\Livewire\Staff\Assignments\AssignmentOverview;
use App\Livewire\Staff\Attendance\AttendanceQueue;
use App\Livewire\Staff\Attendance\DailyAttendanceSheet;
use App\Livewire\Staff\Attendance\MyClasses;
use App\Livewire\Staff\Attendance\QrCardGenerator;
use App\Livewire\Staff\Attendance\ScanEventQueue;
use App\Livewire\Staff\Attendance\SheetVerification;
use App\Livewire\Staff\Attendance\StaffAttendanceDashboard;
use App\Livewire\Staff\Attendance\StaffAttendanceEntry;
use App\Livewire\Staff\ClassLists\ClassList;
use App\Livewire\Staff\Corrections\CorrectionInbox;
use App\Livewire\Staff\Corrections\CorrectionReview;
use App\Livewire\Staff\Dashboard;
use App\Livewire\Staff\Documents\DocumentReview;
use App\Livewire\Staff\Documents\DocumentReviewQueue;
use App\Livewire\Staff\Enrollments\EnrollmentManagement;
use App\Livewire\Staff\Enrollments\PromotionReview;
use App\Livewire\Staff\Enrollments\TransferStudent;
use App\Livewire\Staff\FormalRequests\FormalRequestDetail;
use App\Livewire\Staff\FormalRequests\FormalRequestList;
use App\Livewire\Staff\FormalRequests\NewFormalRequest;
use App\Livewire\Staff\Imports\ImportBatchDetail;
use App\Livewire\Staff\Imports\ImportBatchIndex;
use App\Livewire\Staff\Marks\MarkCorrection;
use App\Livewire\Staff\Marks\MarkEntrySheet;
use App\Livewire\Staff\Marks\MarksVerificationQueue;
use App\Livewire\Staff\Marks\MarkWindowExtension;
use App\Livewire\Staff\Marks\MySubjects;
use App\Livewire\Staff\Reports\AttendanceReport as StaffAttendanceReport;
use App\Livewire\Staff\Reports\ResultReport as StaffResultReport;
use App\Livewire\Staff\Reports\StaffReportCentre;
use App\Livewire\Staff\Students\AddStudent;
use App\Livewire\Staff\Students\GuardianRelationships;
use App\Livewire\Staff\Students\StudentDetail;
use App\Livewire\Staff\Students\StudentList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Portal Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /staff and named staff.*.
|
| Protected routes require the 'staff' guard (StaffAccount only).
| Staff sessions are anonymous in the admin and guardian portals.
|
| Staff authentication grants a staff account actor identity only.
| Institutional operational access additionally requires eligible active
| positions, trusted operational context, and Authorization policies.
|
*/

Route::prefix('staff')->name('staff.')->group(function (): void {

    // ── Login / logout (F10) ─────────────────────────────────────────────

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ── Protected routes ─────────────────────────────────────────────────

    Route::middleware(['auth:staff', 'portal.version:staff'])->group(function (): void {

        // Dashboard — landing page for all authenticated staff
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        // Students
        Route::get('/students', StudentList::class)->name('students.index');
        Route::get('/students/add', AddStudent::class)->name('students.add');
        Route::get('/students/{studentProfileId}', StudentDetail::class)
            ->where('studentProfileId', '[0-9]+')
            ->name('students.detail');
        Route::get('/students/{studentProfileId}/relationships', GuardianRelationships::class)
            ->where('studentProfileId', '[0-9]+')
            ->name('students.relationships');

        // Class lists (teacher-accessible read-only)
        Route::get('/class-lists', ClassList::class)->name('class-lists.index');

        // Assignments (principal/deputy read-only overview)
        Route::get('/assignments', AssignmentOverview::class)->name('assignments.index');

        // Enrollments
        Route::get('/enrollments', EnrollmentManagement::class)->name('enrollments.index');
        Route::get('/enrollments/transfer/{studentProfileId}', TransferStudent::class)
            ->where('studentProfileId', '[0-9]+')
            ->name('enrollments.transfer');
        Route::get('/promotions', PromotionReview::class)->name('promotions.index');

        // Attendance — teacher daily entry and secretary review
        Route::get('/attendance', MyClasses::class)->name('attendance.index');
        Route::get('/attendance/sheet', DailyAttendanceSheet::class)->name('attendance.sheet');
        Route::get('/attendance/queue', AttendanceQueue::class)->name('attendance.queue');
        Route::get('/attendance/verify/{sheetId}', SheetVerification::class)
            ->where('sheetId', '[0-9]+')
            ->name('attendance.verify');

        // Staff attendance — secretary entry and QR management
        Route::get('/staff-attendance', StaffAttendanceEntry::class)->name('staff-attendance.index');
        Route::get('/staff-attendance/dashboard', StaffAttendanceDashboard::class)->name('staff-attendance.dashboard');
        Route::get('/staff-attendance/scan-queue', ScanEventQueue::class)->name('staff-attendance.scan-queue');
        Route::get('/staff-attendance/qr-cards', QrCardGenerator::class)->name('staff-attendance.qr-cards');
        Route::get('/staff-attendance/scan-form', static fn () => view('staff.attendance.scan-form', [
            'periods' => DB::table('operational_periods')->select('id', 'name')->orderBy('name')->get(),
        ]))->name('attendance.scan-form');

        // Marks — teacher entry, secretary verification, principal approval, corrections
        Route::get('/marks', MySubjects::class)->name('marks.index');
        Route::get('/marks/sheet/{assignmentId}', MarkEntrySheet::class)
            ->where('assignmentId', '[0-9]+')
            ->name('marks.sheet');
        Route::get('/marks/review', MarksVerificationQueue::class)->name('marks.review');
        Route::get('/marks/windows', MarkWindowExtension::class)->name('marks.windows');
        Route::get('/marks/correct/{sheetId}', MarkCorrection::class)
            ->where('sheetId', '[0-9]+')
            ->name('marks.correct');

        // Imports (secretary/deputy_principal)
        Route::get('/imports', ImportBatchIndex::class)->name('imports.index');
        Route::get('/imports/{batchId}', ImportBatchDetail::class)
            ->where('batchId', '[0-9]+')
            ->name('imports.detail');

        // Reports — attendance and marks/results (scoped to staff position)
        Route::get('/reports/attendance', StaffAttendanceReport::class)->name('reports.attendance');
        Route::get('/reports/results', StaffResultReport::class)->name('reports.results');

        // Report centre — scoped, period-restricted report browser
        Route::get('/reports', StaffReportCentre::class)->name('reports.centre');

        // Report file download (path encrypted by Livewire export actions)
        Route::get('/reports/download', ReportDownloadController::class)->name('reports.download');

        // Correction request review (secretary / principal)
        Route::get('/corrections', CorrectionInbox::class)->name('corrections.index');
        Route::get('/corrections/{requestId}', CorrectionReview::class)
            ->where('requestId', '[0-9]+')
            ->name('corrections.review');

        // Secure attachment download — authorization gated, no public URL
        Route::get('/attachments/{attachment}', SecureAttachmentDownloadController::class)
            ->name('attachments.download');

        // Formal institution requests (secretary prepare, principal/deputy review+sign)
        Route::get('/formal-requests', FormalRequestList::class)->name('formal-requests.index');
        Route::get('/formal-requests/new', NewFormalRequest::class)->name('formal-requests.new');
        Route::get('/formal-requests/{requestId}', FormalRequestDetail::class)
            ->where('requestId', '[0-9]+')
            ->name('formal-requests.detail');

        // Document request review (secretary)
        Route::get('/documents', DocumentReviewQueue::class)->name('documents.queue');
        Route::get('/documents/{requestId}', DocumentReview::class)
            ->where('requestId', '[0-9]+')
            ->name('documents.review');
        Route::get('/documents/download/{documentId}', IssuedDocumentDownloadController::class)
            ->where('documentId', '[0-9]+')
            ->name('documents.download');
    });

    // ── Unprotected smoke-test placeholder ────────────────────────────────

    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
