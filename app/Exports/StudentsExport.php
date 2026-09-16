<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class StudentsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Collection $students
    ) {
    }

    public function collection(): Collection
    {
        return $this->students->map(function ($student): array {
            return [
                $student->national_id,
                $student->full_name_ar,
                $student->full_name_en ?? '',
                $student->birth_date ?? '',
                $student->lifecycle_status,
                $student->registered_on ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('ui.student_code', [], null, 'Student Code'),
            __('ui.name', [], null, 'Name'),
            __('ui.name_en', [], null, 'English Name'),
            __('ui.birth_date', [], null, 'Birth Date'),
            __('ui.status', [], null, 'Status'),
            __('ui.registered', [], null, 'Registered'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
