<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Calendar;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Academic calendar overview: years → semesters → institution semesters.
 */
final class CalendarIndex extends Component
{
    use HasAdminAuth;

    public ?int $selectedYearId = null;

    public function mount(): void
    {
        $this->requirePermission('academic_year.view');
    }

    public function academicYears(): Collection
    {
        return DB::table('academic_years')
            ->orderByDesc('starts_on')
            ->get();
    }

    public function semesters(): Collection
    {
        if ($this->selectedYearId === null) {
            return collect();
        }

        return DB::table('semesters')
            ->where('academic_year_id', $this->selectedYearId)
            ->orderBy('starts_on')
            ->get();
    }

    public function institutionSemesters(): Collection
    {
        if ($this->selectedYearId === null) {
            return collect();
        }

        return DB::table('institution_semesters as is')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->where('s.academic_year_id', $this->selectedYearId)
            ->select(
                'is.id', 'is.status', 'is.semester_id',
                'i.name_ar as institution_name_ar', 'i.name_en as institution_name_en',
                's.name_ar as semester_name_ar',
            )
            ->orderBy('i.name_ar')
            ->get();
    }

    public function selectYear(int $yearId): void
    {
        $this->selectedYearId = $yearId;
    }

    public function render(): View
    {
        return view('livewire.admin.calendar.index', [
            'academicYears' => $this->academicYears(),
            'semesters' => $this->semesters(),
            'institutionSemesters' => $this->institutionSemesters(),
        ])->layout('layouts.admin');
    }
}
