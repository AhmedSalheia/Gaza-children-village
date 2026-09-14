<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ClassStudentsExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles
{
    public function __construct(
        private readonly Collection $students
    ) {
    }

    /**
     * Excel rows.
     */
    public function collection(): Collection
    {
        return $this->students->map(
            function ($student): array {
                return [
                    $student->national_id ?? '',
                    $student->name_ar ?? '',
                    $student->name_en ?? '',
                    $student->enrollment_status ?? '',
                    $student->enrolled_on ?? '',
                ];
            }
        );
    }

    /**
     * Excel headings.
     */
    public function headings(): array
    {
        return [
            __('ui.student_code'),
            __('ui.name_ar'),
            __('ui.name_en'),
            __('ui.status'),
            __('ui.enrolled_on'),
        ];
    }

    /**
     * Excel styling.
     */
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

