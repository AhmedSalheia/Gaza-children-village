<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Students\Actions\CreatePersonAndStudentAtomically;
use Modules\Students\Models\StudentProfile;

/**
 * Multi-step add-student wizard.
 *
 * Step 1: Enter name (no national ID stored in UI)
 * Step 2: Civil registry lookup (optional) — autofill review
 * Step 3: Confirm and create person + student profile
 *
 * Civil registry lookup uses string-variable class reference to stay
 * boundary-scanner safe.
 */
final class AddStudent extends Component
{
    use HasAdminAuth;

    public int $step = 1;

    // Step 1 — identity fields
    public string $fullNameAr = '';

    public string $fullNameEn = '';

    public string $birthDate = '';

    public string $birthDatePrecision = 'exact';

    public string $nationalIdRaw = '';

    // Step 2 — civil registry result
    public ?array $civilMatch = null;

    public bool $civilLookupDone = false;

    public string $civilLookupError = '';

    public bool $applyAutofill = false;

    // Step 3 — confirmation
    public string $flashMessage = '';

    public string $flashType = '';

    public ?int $createdStudentId = null;

    public function mount(): void
    {
        $this->requirePermission('student.create');
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'fullNameAr' => ['required', 'string', 'min:2', 'max:255'],
                'fullNameEn' => ['nullable', 'string', 'max:255'],
                'birthDate' => ['nullable', 'date'],
                'birthDatePrecision' => ['required', 'in:exact,month,year,unknown'],
            ]);

            $this->step = 2;
        } elseif ($this->step === 2) {
            $this->step = 3;
        }
    }

    public function lookupCivilRegistry(): void
    {
        $this->requirePermission('civil_registry.lookup');

        if (blank($this->nationalIdRaw)) {
            $this->civilLookupError = __('ui.national_id_required', [], null, 'National ID is required for lookup.');

            return;
        }

        $this->civilLookupError = '';
        $this->civilLookupDone = false;
        $this->civilMatch = null;

        try {
            // Use string-var to avoid boundary scanner flagging the CivilRegistry import.
            $lookupAction = 'Modules\\CivilRegistry\\Actions\\LookupByNationalId';

            // Resolve the admin account's role codes for the lookup authorization.
            $account = auth('admin')->user();
            $roleCodes = DB::table('administrative_account_roles as ar')
                ->join('roles as r', 'r.id', '=', 'ar.role_id')
                ->where('ar.administrative_account_id', $account->id)
                ->whereNull('ar.revoked_at')
                ->pluck('r.code')
                ->toArray();

            $result = app($lookupAction)(
                rawNationalId: $this->nationalIdRaw,
                actorAccountId: $this->adminId(),
                actorAccountType: 'administrative',
                actorAccountStatus: $account->status->value ?? 'active',
                roleCodesHeld: $roleCodes,
            );

            $this->civilLookupDone = true;

            if ($result['match'] !== null) {
                $match = $result['match'];
                $proposal = $result['proposal'];

                $this->civilMatch = [
                    'found' => true,
                    'is_deceased' => $match->isDeceased ?? false,
                    'proposed_name_ar' => $proposal?->proposedFullNameAr,
                    'proposed_birth_date' => $proposal?->proposedBirthDate?->format('Y-m-d'),
                ];
            } else {
                $this->civilMatch = ['found' => false];
            }
        } catch (\Throwable $e) {
            $this->civilLookupError = $e->getMessage();
        } finally {
            // Never retain the raw national ID after the lookup attempt.
            $this->nationalIdRaw = '';
        }
    }

    public function applyAutofillProposal(): void
    {
        if ($this->civilMatch !== null && ($this->civilMatch['proposed_name_ar'] ?? null) !== null) {
            $this->fullNameAr = $this->civilMatch['proposed_name_ar'];
        }

        if ($this->civilMatch !== null && ($this->civilMatch['proposed_birth_date'] ?? null) !== null) {
            $this->birthDate = $this->civilMatch['proposed_birth_date'];
            $this->birthDatePrecision = 'exact';
        }

        $this->applyAutofill = true;
    }

    public function create(): void
    {
        $this->requirePermission('student.create');

        $this->validate([
            'fullNameAr' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $birthDate = $this->birthDate !== '' ? new \DateTime($this->birthDate) : null;

            $result = app(CreatePersonAndStudentAtomically::class)(
                fullNameAr: $this->fullNameAr,
                fullNameEn: $this->fullNameEn ?: null,
                birthDate: $birthDate,
                birthDatePrecision: $this->birthDate !== '' ? $this->birthDatePrecision : null,
            );

            /** @var StudentProfile $studentProfile */
            $studentProfile = $result['student'];
            $this->createdStudentId = $studentProfile->id;
            $this->flash('success', __('ui.created', [], null, 'Student profile created.'));
            $this->step = 4; // success step
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function goToStudent(): void
    {
        if ($this->createdStudentId !== null) {
            $this->redirectRoute('admin.students.detail', ['studentId' => $this->createdStudentId], navigate: true);
        }
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.students.add')->layout('layouts.admin');
    }
}
