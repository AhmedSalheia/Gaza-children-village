<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\ListEnrollmentHistory;
use Modules\Students\Models\StudentProfile;

/**
 * Student profile detail view: identity, guardians, enrollment history.
 */
final class StudentDetail extends Component
{
    use HasAdminAuth;

    public int $studentId;

    public ?object $student = null;

    public ?object $person = null;

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(int $studentId): void
    {
        $this->requirePermission('student.view');
        $this->studentId = $studentId;

        $this->student = DB::table('student_profiles')->where('id', $studentId)->first();

        if ($this->student === null) {
            $this->redirectRoute('admin.students.index', navigate: true);

            return;
        }

        $this->person = DB::table('people')->where('id', $this->student->person_id)->first();
    }

    public function guardianRelationships(): Collection
    {
        return DB::table('guardian_student_relationships as gsr')
            ->join('guardian_profiles as gp', 'gp.id', '=', 'gsr.guardian_profile_id')
            ->join('people as p', 'p.id', '=', 'gp.person_id')
            ->where('gsr.student_profile_id', $this->studentId)
            ->get([
                'gsr.id',
                'gsr.relationship_type',
                'gsr.verification_status',
                'gsr.portal_eligible',
                'gsr.ends_on',
                'gp.id as guardian_id',
                'gp.guardian_code',
                'p.full_name_ar',
                'p.full_name_en',
            ]);
    }

    public function enrollmentHistory(): Collection
    {
        $studentProfile = StudentProfile::find($this->studentId);

        if ($studentProfile === null) {
            return collect();
        }

        return app(ListEnrollmentHistory::class)($studentProfile->id);
    }

    public function render(): View
    {
        return view('livewire.admin.students.detail', [
            'guardianRelationships' => $this->guardianRelationships(),
            'enrollmentHistory' => $this->enrollmentHistory(),
        ])->layout('layouts.admin');
    }
}
