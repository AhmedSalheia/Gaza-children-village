<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Guardian profile detail view with student relationship summary.
 */
final class GuardianDetail extends Component
{
    use HasAdminAuth;

    public int $guardianId;

    public ?object $guardian = null;

    public ?object $person = null;

    public function mount(int $guardianId): void
    {
        $this->requirePermission('guardian_relationship.view');
        $this->guardianId = $guardianId;

        $this->guardian = DB::table('guardian_profiles')->where('id', $guardianId)->first();

        if ($this->guardian === null) {
            $this->redirectRoute('admin.guardians.index', navigate: true);

            return;
        }

        $this->person = DB::table('people')->where('id', $this->guardian->person_id)->first();
    }

    public function relationships(): Collection
    {
        return DB::table('guardian_student_relationships as gsr')
            ->join('student_profiles as sp', 'sp.id', '=', 'gsr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('gsr.guardian_profile_id', $this->guardianId)
            ->get([
                'gsr.id',
                'gsr.relationship_type',
                'gsr.verification_status',
                'gsr.portal_eligible',
                'gsr.ends_on',
                'sp.id as student_id',
                'sp.student_code',
                'p.full_name_ar as student_name_ar',
            ]);
    }

    public function render(): View
    {
        return view('livewire.admin.students.guardian-detail', [
            'relationships' => $this->relationships(),
        ])->layout('layouts.admin');
    }
}
