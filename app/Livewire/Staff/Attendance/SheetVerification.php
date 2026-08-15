<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Attendance\Actions\CorrectVerifiedAttendance;
use Modules\Attendance\Actions\ReopenForCorrection;
use Modules\Attendance\Actions\ReturnSheet;
use Modules\Attendance\Actions\VerifySheet;
use Modules\Attendance\Data\StudentAttendanceStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;
use Modules\Authorization\Data\PermissionKey;

/**
 * Secretary/principal sheet verification view.
 *
 * AUTHORIZATION MODEL
 * -------------------
 * • mount() calls assertSheetInScope(), which enforces that the sheet belongs
 *   to the staff member's institution semester and, for period-restricted
 *   positions, that its class group is in their allowed operational periods.
 * • Every public mutation re-asserts scope to block forged Livewire messages.
 * • Permission checks (RETURN, VERIFY, CORRECT) are layered on top.
 *
 * Allows the authorised staff member to:
 *  - View the full roster with attendance entries
 *  - Return the sheet to the teacher with a reason
 *  - Verify (approve) a submitted sheet
 *  - Re-verify a reopened sheet after corrections
 *  - Reopen a verified sheet for post-verification correction
 *  - Correct individual records on a reopened sheet
 */
final class SheetVerification extends Component
{
    use HasStaffAuth;

    public int $sheetId;

    public string $flashMessage = '';
    public string $flashType    = 'success';

    // Return form
    public bool   $showReturn   = false;
    public string $returnReason = '';

    // Correction form
    public bool    $showCorrect         = false;
    public int     $correctEnrollmentId = 0;
    public string  $correctStatusCode   = '';
    public string  $correctReason       = '';
    public string  $correctArrivedAt    = '';
    public string  $correctDepartedAt   = '';

    private ?AttendanceSheet $sheetCache = null;

    public function mount(int $sheetId): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_RETURN);
        $this->sheetId = $sheetId;

        // Assert scope immediately — blocks cross-period and cross-institution access
        // via direct URL, not just via the queue listing.
        $this->assertSheetInScope($this->sheetId);
    }

    // ── Sheet accessor ────────────────────────────────────────────────────

    public function sheet(): ?AttendanceSheet
    {
        return $this->sheetCache ??= AttendanceSheet::find($this->sheetId);
    }

    public function records(): \Illuminate\Support\Collection
    {
        return DB::table('student_attendance_records as sar')
            ->join('student_enrollments as se', 'se.id', '=', 'sar.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sar.sheet_id', $this->sheetId)
            ->select(
                'sar.id',
                'sar.enrollment_id',
                'sar.status_code',
                'sar.reason',
                'sar.arrived_at',
                'sar.departed_at',
                'sar.correction_cycle',
                'sar.safe_note',
                'p.full_name_ar as student_name',
            )
            ->orderBy('p.full_name_ar')
            ->get();
    }

    /**
     * Load the full append-only correction history for all records on this sheet,
     * keyed by enrollment_id → ordered list of history entries (oldest first).
     *
     * The UI reads from this table rather than the mutable previous_status_code
     * column so all correction cycles are visible.
     *
     * @return array<int, \Illuminate\Support\Collection>
     */
    public function correctionHistory(): array
    {
        $history = DB::table('student_attendance_correction_history')
            ->where('sheet_id', $this->sheetId)
            ->orderBy('correction_cycle')
            ->get();

        $grouped = [];

        foreach ($history as $row) {
            $grouped[(int) $row->enrollment_id][] = $row;
        }

        return $grouped;
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function startReturn(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_RETURN);
        $this->showReturn   = true;
        $this->returnReason = '';
    }

    public function confirmReturn(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_RETURN);
        $this->assertSheetInScope($this->sheetId);

        try {
            app(ReturnSheet::class)($this->sheet(), $this->returnReason, $this->staffProfileId());
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
        $this->assertSheetInScope($this->sheetId);

        try {
            app(VerifySheet::class)($this->sheet(), $this->staffProfileId());
            $this->sheetCache = null;
            $this->flash('Sheet verified and locked.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function reopen(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_CORRECT);
        $this->assertSheetInScope($this->sheetId);

        try {
            app(ReopenForCorrection::class)($this->sheet(), $this->staffProfileId());
            $this->sheetCache = null;
            $this->flash('Sheet reopened for corrections.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function startCorrect(int $enrollmentId, string $currentStatus): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_CORRECT);
        $this->showCorrect         = true;
        $this->correctEnrollmentId = $enrollmentId;
        $this->correctStatusCode   = $currentStatus;
        $this->correctReason       = '';
        $this->correctArrivedAt    = '';
        $this->correctDepartedAt   = '';
    }

    public function confirmCorrect(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_CORRECT);
        $this->assertSheetInScope($this->sheetId);

        try {
            app(CorrectVerifiedAttendance::class)(
                sheet: $this->sheet(),
                enrollmentId: $this->correctEnrollmentId,
                newStatusCode: $this->correctStatusCode,
                reason: $this->correctReason !== '' ? $this->correctReason : null,
                actorStaffProfileId: $this->staffProfileId(),
                arrivedAt: $this->correctArrivedAt !== '' ? $this->correctArrivedAt : null,
                departedAt: $this->correctDepartedAt !== '' ? $this->correctDepartedAt : null,
            );
            $this->sheetCache  = null;
            $this->showCorrect = false;
            $this->flash('Record corrected. Previous value preserved in history.', 'success');
        } catch (AttendanceException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.verification', [
            'sheet'             => $this->sheet(),
            'records'           => $this->records(),
            'statuses'          => StudentAttendanceStatus::catalogue(),
            'correctionHistory' => $this->correctionHistory(),
        ]);
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;
    }
}
