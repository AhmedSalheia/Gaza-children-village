<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Attendance\Actions\GenerateQrCredential;
use Modules\Attendance\Actions\RevokeQrCredential;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffQrCredential;

/**
 * Secretary QR card generation and credential management.
 *
 * Displays the current credential status for each staff member assigned to
 * the secretary's institution. Allows generating (or regenerating) individual
 * credentials. Revocation is also supported.
 *
 * The plaintext token is shown ONCE immediately after generation together with
 * a server-rendered SVG QR code (via endroid/qr-code). Staff must print or
 * save the card at that moment — the token cannot be retrieved again.
 */
final class QrCardGenerator extends Component
{
    use HasStaffAuth;

    /** @var int|null Staff profile ID whose newly generated token is displayed */
    public ?int $generatedStaffProfileId = null;

    /** @var string|null Plaintext token shown once post-generation */
    public ?string $generatedPlaintextToken = null;

    /** Staff name for the generated card */
    public string $generatedStaffName = '';

    /** Server-rendered SVG markup for the printable QR card */
    public string $generatedQrSvg = '';

    public string $flashMessage = '';
    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('staff_attendance.enter');
    }

    public function generateCredential(int $staffProfileId): void
    {
        $this->requirePermission('staff_attendance.enter');

        // Institution guard: only generate for staff in this secretary's institution
        $scope = $this->staffScope();

        if ($scope['institution_id'] !== null) {
            $belongs = DB::table('staff_institution_assignments')
                ->where('staff_profile_id', $staffProfileId)
                ->where('institution_id', $scope['institution_id'])
                ->whereNull('ended_on')
                ->exists();

            if (! $belongs) {
                abort(403, 'Staff member is not assigned to your institution.');
            }
        }

        try {
            $result = app(GenerateQrCredential::class)(
                staffProfileId:          $staffProfileId,
                issuedByStaffProfileId:  $this->resolveStaffProfileId(),
            );

            $this->generatedStaffProfileId  = $staffProfileId;
            $this->generatedPlaintextToken  = $result['plaintext_token'];
            $this->generatedStaffName       = $this->staffName($staffProfileId);
            $this->generatedQrSvg           = $this->buildQrSvg($result['plaintext_token']);
            $this->flashMessage             = "New credential generated for {$this->generatedStaffName}. Print or save the QR card now.";
            $this->flashType                = 'success';
        } catch (StaffAttendanceException $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashType    = 'error';
        }
    }

    public function revokeCredential(int $credentialId): void
    {
        $this->requirePermission('staff_attendance.enter');

        $credential = StaffQrCredential::find($credentialId);

        if (! $credential) {
            return;
        }

        // Institution guard: only revoke credentials for staff in this institution
        $scope = $this->staffScope();

        if ($scope['institution_id'] !== null) {
            $belongs = DB::table('staff_institution_assignments')
                ->where('staff_profile_id', $credential->staff_profile_id)
                ->where('institution_id', $scope['institution_id'])
                ->whereNull('ended_on')
                ->exists();

            if (! $belongs) {
                abort(403, 'Credential belongs to a staff member outside your institution.');
            }
        }

        try {
            app(RevokeQrCredential::class)($credential, $this->resolveStaffProfileId());
            $this->flashMessage = 'Credential revoked.';
            $this->flashType    = 'success';
        } catch (StaffAttendanceException $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashType    = 'error';
        }
    }

    public function dismissGeneratedToken(): void
    {
        $this->generatedStaffProfileId = null;
        $this->generatedPlaintextToken = null;
        $this->generatedStaffName      = '';
        $this->generatedQrSvg          = '';
    }

    /** @return \Illuminate\Support\Collection */
    public function staffList(): \Illuminate\Support\Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return collect();
        }

        return DB::table('staff_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('staff_institution_assignments as sia', function ($j): void {
                $j->on('sia.staff_profile_id', '=', 'sp.id')
                  ->whereNull('sia.ended_on');
            })
            ->where('sia.institution_id', $scope['institution_id'])
            ->leftJoin('staff_qr_credentials as sqc', function ($j): void {
                $j->on('sqc.staff_profile_id', '=', 'sp.id')
                  ->where('sqc.is_active', true);
            })
            ->select(
                'sp.id as staff_profile_id',
                'p.full_name_ar as name',
                'sqc.id as credential_id',
                'sqc.issued_at',
                'sqc.is_active',
            )
            ->orderBy('p.full_name_ar')
            ->distinct()
            ->get();
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.qr-card-generator', [
            'staffList' => $this->staffList(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Generate a compact SVG QR code for the given plaintext token.
     * Uses endroid/qr-code v5 SvgWriter — no JavaScript required.
     */
    private function buildQrSvg(string $token): string
    {
        $qrCode = QrCode::create($token)
            ->setSize(220)
            ->setMargin(4);

        $writer = new SvgWriter();
        $result = $writer->write($qrCode, options: [
            SvgWriter::WRITER_OPTION_COMPACT                  => true,
            SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION  => true,
        ]);

        return $result->getString();
    }

    private function resolveStaffProfileId(): int
    {
        $profileId = $this->staffProfileId();

        if ($profileId === null) {
            abort(403, 'No staff profile linked to this account.');
        }

        return $profileId;
    }

    private function staffName(int $staffProfileId): string
    {
        return (string) DB::table('staff_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $staffProfileId)
            ->value('p.full_name_ar');
    }
}
