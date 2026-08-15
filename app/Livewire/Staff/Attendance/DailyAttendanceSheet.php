<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Attendance\Actions\BulkMarkPresent;
use Modules\Attendance\Actions\OpenDailySheet;
use Modules\Attendance\Actions\ReturnSheet;
use Modules\Attendance\Actions\SubmitSheet;
use Modules\Attendance\Actions\UpdateRecord;
use Modules\Attendance\Actions\VerifySheet;
use Modules\Attendance\Data\StudentAttendanceStatus;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;
use Modules\Authorization\Data\PermissionKey;

/**
 * Daily attendance entry sheet for teachers and secretaries.
 *
 * AUTHORIZATION MODEL
 * -------------------
 * • Every access (mount, open, mutations) calls assertSheetInScope() which
 *   verifies institution_semester_id matches and, for period-restricted
 *   positions, that the class group's operational_period is in allowedPeriodIds().
 * • Teachers additionally require an active homeroom assignment for the class
 *   (checked via assertHomeroomIfTeacher). Staff with VERIFY permission
 *   (secretary, principal) skip the homeroom check.
 *
 * The route supplies either:
 *  - sheetId  (existing sheet) → scope-checked then loaded
 *  - classGroupId + date       → scope-checked then opened or loaded
 */
final class DailyAttendanceSheet extends Component
{
    use HasStaffAuth;

    // ── Route params ──────────────────────────────────────────────────────
    public ?int    $sheetId      = null;
    public ?int    $classGroupId = null;
    public ?string $date         = null;

    // ── UI state ─────────────────────────────────────────────────────────
    public string  $flashMessage = '';
    public string  $flashType    = 'success';
    public string  $returnReason = '';
    public bool    $showReturn   = false;

    private ?AttendanceSheet $sheetCache = null;

    public function mount(?int $sheetId = null, ?int $classGroupId = null, ?string $date = null): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_ENTER);

        $this->sheetId      = $sheetId;
        $this->classGroupId = $classGroupId;
        $this->date         = $date ?? now()->toDateString();

        if ($this->sheetId !== null) {
            // Scope-check the given sheet before showing anything.
            $sheetRow            = $this->assertSheetInScope($this->sheetId);
            $this->classGroupId  = (int) $sheetRow->class_group_id;
            $this->assertHomeroomIfTeacher($this->classGroupId);
        } elseif ($this->classGroupId !== null) {
            // Scope-check the target class group, then open or load its sheet.
            $this->assertClassGroupInScope($this->classGroupId);
            $this->assertHomeroomIfTeacher($this->classGroupId);
            $this->openOrLoadSheet();
        }
    }

    // ── Sheet open / load ─────────────────────────────────────────────────

    private function openOrLoadSheet(): void
    {
        $existing = DB::table('student_attendance_sheets')
            ->where('class_group_id', $this->classGroupId)
            ->whereDate('attendance_date', $this->date)
            ->orderByDesc('id')
            ->value('id');

        if ($existing) {
            $this->sheetId = (int) $existing;

            return;
        }

        try {
            $sheet         = app(OpenDailySheet::class)(
                classGroupId: $this->classGroupId,
                date: new \DateTimeImmutable($this->date),
                creatorStaffProfileId: $this->staffProfileId(),
            );
            $this->sheetId = $sheet->id;
            $this->flash('Attendance sheet opened.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    // ── Homeroom guard (teacher-specific) ─────────────────────────────────

    /**
     * Abort 403 if the current user is a teacher (no VERIFY permission) and
     * does not hold an active homeroom assignment for the given class group.
     *
     * Staff with VERIFY permission (secretary, principal, deputy) may access
     * any in-scope sheet regardless of homeroom assignment.
     */
    private function assertHomeroomIfTeacher(int $classGroupId): void
    {
        if ($this->staffCan(PermissionKey::STUDENT_ATTENDANCE_VERIFY)) {
            return;
        }

        $profileId = $this->staffProfileId();
        $scope     = $this->staffScope();

        $hasHomeroom = DB::table('homeroom_assignments')
            ->where('staff_profile_id', $profileId)
            ->where('class_group_id', $classGroupId)
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->where('status', 'active')
            ->exists();

        if (! $hasHomeroom) {
            abort(403, 'You do not have a homeroom assignment for this class.');
        }
    }

    // ── Sheet accessor ────────────────────────────────────────────────────

    public function sheet(): ?AttendanceSheet
    {
        if ($this->sheetId === null) {
            return null;
        }

        return $this->sheetCache ??= AttendanceSheet::find($this->sheetId);
    }

    public function records(): \Illuminate\Support\Collection
    {
        $sheet = $this->sheet();

        if (! $sheet) {
            return collect();
        }

        return DB::table('student_attendance_records as sar')
            ->join('student_enrollments as se', 'se.id', '=', 'sar.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sar.sheet_id', $sheet->id)
            ->select(
                'sar.id',
                'sar.enrollment_id',
                'sar.student_profile_id',
                'sar.status_code',
                'sar.reason',
                'sar.arrived_at',
                'sar.departed_at',
                'sar.safe_note',
                'sar.previous_status_code',
                'sar.corrected_at',
                'p.full_name_ar as student_name',
            )
            ->orderBy('p.full_name_ar')
            ->get();
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    /**
     * Save a single record row.
     *
     * Called from the blade via Alpine.js:
     *   $wire.saveRow(enrollmentId, statusCode, reason, arrivedAt, departedAt)
     *
     * Scope is re-asserted on every mutation so a forged Livewire message
     * cannot bypass the mount-time check.
     */
    public function saveRow(
        int $enrollmentId,
        string $statusCode,
        string $reason = '',
        string $arrivedAt = '',
        string $departedAt = '',
    ): void {
        if ($this->sheetId === null) {
            return;
        }

        // Re-assert scope and homeroom on every mutation.
        $sheetRow = $this->assertSheetInScope($this->sheetId);
        $this->assertHomeroomIfTeacher((int) $sheetRow->class_group_id);

        $sheet = $this->sheet();

        if (! $sheet) {
            return;
        }

        try {
            app(UpdateRecord::class)(
                sheet: $sheet,
                enrollmentId: $enrollmentId,
                statusCode: $statusCode,
                reason: $reason !== '' ? $reason : null,
                arrivedAt: $arrivedAt !== '' ? $arrivedAt : null,
                departedAt: $departedAt !== '' ? $departedAt : null,
            );
            $this->sheetCache = null;
            $this->flash('Record saved.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function bulkMarkPresent(): void
    {
        if ($this->sheetId === null) {
            return;
        }

        $sheetRow = $this->assertSheetInScope($this->sheetId);
        $this->assertHomeroomIfTeacher((int) $sheetRow->class_group_id);

        $sheet = $this->sheet();

        if (! $sheet) {
            return;
        }

        try {
            $count            = app(BulkMarkPresent::class)($sheet);
            $this->sheetCache = null;
            $this->flash("Marked {$count} students as present.", 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function submit(): void
    {
        if ($this->sheetId === null) {
            return;
        }

        $sheetRow = $this->assertSheetInScope($this->sheetId);
        $this->assertHomeroomIfTeacher((int) $sheetRow->class_group_id);

        $sheet = $this->sheet();

        if (! $sheet) {
            return;
        }

        try {
            app(SubmitSheet::class)($sheet, $this->staffProfileId());
            $this->sheetCache = null;
            $this->flash('Sheet submitted for secretary review.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function startReturn(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_RETURN);
        $this->showReturn   = true;
        $this->returnReason = '';
    }

    public function confirmReturn(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_RETURN);

        if ($this->sheetId === null) {
            return;
        }

        $this->assertSheetInScope($this->sheetId);

        $sheet = $this->sheet();

        if (! $sheet) {
            return;
        }

        try {
            app(ReturnSheet::class)($sheet, $this->returnReason, $this->staffProfileId());
            $this->sheetCache = null;
            $this->showReturn = false;
            $this->flash('Sheet returned to teacher.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function verify(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_VERIFY);

        if ($this->sheetId === null) {
            return;
        }

        $this->assertSheetInScope($this->sheetId);

        $sheet = $this->sheet();

        if (! $sheet) {
            return;
        }

        try {
            app(VerifySheet::class)($sheet, $this->staffProfileId());
            $this->sheetCache = null;
            $this->flash('Sheet verified.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.daily-sheet', [
            'sheet'     => $this->sheet(),
            'records'   => $this->records(),
            'statuses'  => StudentAttendanceStatus::catalogue(),
            'canManage' => $this->staffCan(PermissionKey::STUDENT_ATTENDANCE_VERIFY),
        ]);
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;
    }
}
