<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class StaffStudentsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $statusFilter = '',
        private readonly int $classGroupFilter = 0,
        private readonly int $levelFilter = 0,
        private readonly array $staffScope = [],
        private readonly bool $isFullScope = false,
        private readonly array $allowedPeriods = [],
    ) {
    }

    /**
     * Query used by Laravel Excel.
     */
    public function query(): Builder
    {
        $institutionSemesterId =
            $this->staffScope['institution_semester_id'] ?? null;

        /*
         * No active institution semester.
         */
        if ($institutionSemesterId === null) {
            return DB::table('student_enrollments as se')
                ->whereRaw('1 = 0');
        }

        $query = DB::table('student_enrollments as se')
            ->join(
                'student_profiles as sp',
                'sp.id',
                '=',
                'se.student_profile_id'
            )
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->join(
                'class_groups as cg',
                'cg.id',
                '=',
                'se.class_group_id'
            )
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->where(
                'se.institution_semester_id',
                $institutionSemesterId
            )
            ->whereNotIn(
                'se.enrollment_status',
                [
                    'completed',
                    'withdrawn',
                ]
            )
            ->select(
                'sp.student_code',
                'p.national_id',
                'p.full_name_ar as name_ar',
                'p.full_name_en as name_en',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'se.enrollment_status',
            );

        /*
         * F16 period restriction.
         *
         * Full-scope positions:
         * see all periods.
         *
         * Restricted positions:
         * see only explicitly granted periods.
         */
        if (! $this->isFullScope) {
            if (empty($this->allowedPeriods)) {
                return DB::table('student_enrollments as se')
                    ->whereRaw('1 = 0');
            }

            $query->whereIn(
                'cg.operational_period_id',
                $this->allowedPeriods
            );
        }

        /*
         * Search filter.
         */
        if ($this->search !== '') {
            $query->where(function (Builder $q): void {
                $q->where(
                    'p.full_name_ar',
                    'like',
                    '%'.$this->search.'%'
                )
                ->orWhere(
                    'p.full_name_en',
                    'like',
                    '%'.$this->search.'%'
                )
                ->orWhere(
                    'sp.student_code',
                    'like',
                    '%'.$this->search.'%'
                );
            });
        }

        /*
         * Status filter.
         */
        if ($this->statusFilter !== '') {
            $query->where(
                'se.enrollment_status',
                $this->statusFilter
            );
        }

        /*
         * Class group filter.
         */
        if ($this->classGroupFilter > 0) {
            $query->where(
                'se.class_group_id',
                $this->classGroupFilter
            );
        }

        /*
         * Academic level filter.
         */
        if ($this->levelFilter > 0) {
            $query->where(
                'cg.academic_level_id',
                $this->levelFilter
            );
        }

        return $query->orderBy('p.full_name_ar');
    }

    /**
     * Excel column headings.
     */
public function headings(): array
{
    return [
        __('ui.national_id', [], null, 'National ID'),
        __('ui.arabic_name', [], null, 'Arabic Name'),
        __('ui.english_name', [], null, 'English Name'),
        __('ui.class_group', [], null, 'Class Group'),
        __('ui.academic_level', [], null, 'Academic Level'),
        __('ui.enrollment_status', [], null, 'Enrollment Status'),
    ];
}

    /**
     * Map database row to Excel row.
     */
    public function map($student): array
    {
        return [
            $student->national_id,
            $student->name_ar,
            $student->name_en,
            $student->class_group_name,
            $student->level_name,
            $this->statusLabel($student->enrollment_status),
        ];
    }

    /**
     * Convert status values to readable Excel labels.
     */
    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'draft' => 'Draft',
            'suspended' => 'Suspended',
            default => $status ?? '',
        };
    }
}
