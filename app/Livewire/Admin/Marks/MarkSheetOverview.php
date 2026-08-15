<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Marks;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Read-only admin dashboard showing mark-sheet status per semester.
 */
final class MarkSheetOverview extends Component
{
    use HasAdminAuth;

    #[Url]
    public int $semesterId = 0;

    public string $flashMessage = '';
    public string $flashType    = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::MARKS_READ);
    }

    public function openSemesters(): Collection
    {
        return DB::table('institution_semesters as is')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->where('is.status', 'open')
            ->orderBy('i.name_ar')
            ->get(['is.id', 'i.name_ar as institution_name', 's.name_ar as semester_name']);
    }

    public function markSheets(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->join('teaching_assignments as ta', 'ta.id', '=', 'ms.teaching_assignment_id')
            ->leftJoin('staff_profiles as sp', 'sp.id', '=', 'ta.staff_profile_id')
            ->leftJoin('people as p', 'p.id', '=', 'sp.person_id')
            ->where('ms.institution_semester_id', $this->semesterId)
            ->where('ms.status', '!=', 'superseded')
            ->orderBy('cg.name_ar')
            ->orderBy('s.name_ar')
            ->get([
                'ms.id', 'ms.status', 'ms.version',
                'ms.submitted_at', 'ms.verified_at', 'ms.approved_at',
                'cg.name_ar as class_group_name',
                's.name_ar as subject_name',
                'p.full_name_ar as teacher_name',
            ]);
    }

    public function stats(): object
    {
        if ($this->semesterId === 0) {
            return (object) ['total' => 0, 'submitted' => 0, 'verified' => 0, 'approved' => 0, 'published' => 0];
        }

        $row = DB::table('mark_sheets')
            ->where('institution_semester_id', $this->semesterId)
            ->where('status', '!=', 'superseded')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = 'verified'  THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN status = 'approved'  THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published
            ")
            ->first();

        return $row ?? (object) ['total' => 0, 'submitted' => 0, 'verified' => 0, 'approved' => 0, 'published' => 0];
    }

    public function render(): View
    {
        return view('livewire.admin.marks.mark-sheet-overview', [
            'openSemesters' => $this->openSemesters(),
            'markSheets'    => $this->markSheets(),
            'stats'         => $this->stats(),
        ])->layout('layouts.admin');
    }
}
