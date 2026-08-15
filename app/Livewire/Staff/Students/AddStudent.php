<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Students;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\View\View;
use Livewire\Component;
use Modules\CivilRegistry\Actions\LookupByNationalId;
use Modules\Students\Actions\CreatePersonAndStudentAtomically;

/**
 * Multi-step Add Student flow for staff.
 *
 * Step 1: enter national ID → trigger civil registry lookup.
 * Step 2: review autofill data, allow edits.
 * Step 3: confirm → create student profile.
 *
 * After a successful save the user is redirected to the student list, NOT
 * the student detail page. The detail page requires the student to be enrolled
 * in the staff's active semester — a newly created student has no enrollment.
 * The secretary should enroll the student from the Enrollment Management page.
 *
 * Requires student.create permission.
 */
final class AddStudent extends Component
{
    use HasStaffAuth;

    public int $step = 1;

    // Step 1 — national ID
    public string $nationalId = '';

    // Step 2 — reviewed/editable data
    public string $fullNameAr = '';

    public string $fullNameEn = '';

    public string $birthDate = '';

    public string $birthDatePrecision = 'full';

    // Civil registry result
    public bool $lookupFound = false;

    public ?string $lookupError = null;

    public function mount(): void
    {
        $this->requirePermission('student.create');
    }

    public function lookup(LookupByNationalId $action): void
    {
        $this->requirePermission('civil_registry.lookup');

        $this->validate(['nationalId' => ['required', 'string', 'min:6', 'max:30']]);

        $scope = $this->staffScope();
        $account = auth('staff')->user();

        try {
            $result = $action(
                rawNationalId: $this->nationalId,
                actorAccountId: $this->staffAccountId(),
                actorAccountType: 'staff',
                actorAccountStatus: $account?->status?->value ?? 'active',
                institutionId: $scope['institution_id'],
            );

            $this->lookupFound = $result['match'] !== null;
            $this->lookupError = null;

            if ($this->lookupFound && $result['proposal'] !== null) {
                $proposal = $result['proposal'];
                $fields = (array) ($proposal->fields ?? []);
                $this->fullNameAr = $fields['full_name_ar'] ?? '';
                $this->fullNameEn = $fields['full_name_en'] ?? '';
                $this->birthDate = $fields['birth_date'] ?? '';
                $this->birthDatePrecision = $fields['birth_date_precision'] ?? 'full';
            }
        } catch (\Throwable $e) {
            $this->lookupError = $e->getMessage();
            $this->lookupFound = false;
        }

        $this->step = 2;
    }

    public function skipLookup(): void
    {
        $this->step = 2;
    }

    public function goToConfirm(): void
    {
        $this->validate([
            'fullNameAr' => ['required', 'string', 'max:200'],
            'fullNameEn' => ['nullable', 'string', 'max:200'],
            'birthDate' => ['nullable', 'date', 'before:today'],
        ]);

        $this->step = 3;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function save(CreatePersonAndStudentAtomically $action): void
    {
        $this->requirePermission('student.create');

        $this->validate([
            'fullNameAr' => ['required', 'string', 'max:200'],
            'fullNameEn' => ['nullable', 'string', 'max:200'],
            'birthDate' => ['nullable', 'date', 'before:today'],
        ]);

        try {
            $action(
                fullNameAr: $this->fullNameAr,
                fullNameEn: $this->fullNameEn ?: null,
                birthDate: $this->birthDate ? new \DateTime($this->birthDate) : null,
                birthDatePrecision: $this->birthDate ? $this->birthDatePrecision : null,
            );

            // Redirect to the student list — NOT the detail page.
            // The detail page requires enrollment in the active semester; a newly
            // created student has no enrollment yet. The secretary should enroll
            // the student from Enrollment Management.
            session()->flash('success', __('ui.student_created_enroll_next', [], null,
                'Student profile created. Go to Enrollment Management to create their enrollment.'));

            $this->redirectRoute('staff.students.index');
        } catch (\Throwable $e) {
            $this->addError('save', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.staff.students.add', [
            'canLookup' => $this->staffCan('civil_registry.lookup'),
        ])->layout('layouts.staff');
    }
}
