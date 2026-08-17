<?php

declare(strict_types=1);

namespace Modules\Reporting\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Reporting\Services\FormulaInjectionSanitizer;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Single-sheet Excel export for any report family.
 *
 * Security guarantees:
 *  - All string cell values are passed through FormulaInjectionSanitizer
 *    before being written to the workbook.
 *  - The first row is frozen (freeze at A2) and auto-filtered so users
 *    can sort and filter without risking formula execution in hidden cells.
 *
 * Layout:
 *  Sheet 1 — "Data":  headings + sanitized data rows
 *  Sheet 2 — "Meta":  report metadata (generated_at, definition, filters, row count)
 */
final class ReportExcelExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly string $definitionCode,
        private readonly string $definitionNameAr,
        private readonly array $headings,
        private readonly Collection $rows,
        private readonly array $scopeSummary,
        private readonly string $locale,
        private readonly FormulaInjectionSanitizer $sanitizer,
    ) {}

    /**
     * @return list<object>
     */
    public function sheets(): array
    {
        return [
            new DataSheet(
                title: $this->definitionNameAr,
                headings: $this->headings,
                rows: $this->rows,
                sanitizer: $this->sanitizer,
            ),
            new MetaSheet(
                definitionCode: $this->definitionCode,
                definitionNameAr: $this->definitionNameAr,
                scopeSummary: $this->scopeSummary,
                rowCount: $this->rows->count(),
                locale: $this->locale,
                sanitizer: $this->sanitizer,
            ),
        ];
    }
}

// ── Data sheet ────────────────────────────────────────────────────────────────

/**
 * @internal  Used only by ReportExcelExport — not part of the public API.
 */
final class DataSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    use RegistersEventListeners;

    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly Collection $rows,
        private readonly FormulaInjectionSanitizer $sanitizer,
    ) {}

    public function title(): string
    {
        return mb_substr($this->title, 0, 31); // Excel sheet name limit
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        return $this->rows->map(function (object $row): array {
            return $this->sanitizer->sanitizeRow((array) $row);
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2EAF5'],
                ],
            ],
        ];
    }

    public static function afterSheet(AfterSheet $event): void
    {
        // Freeze the header row so it stays visible while scrolling
        $event->sheet->getDelegate()->freezePane('A2');

        // Auto-filter on the header row
        $highestColumn = $event->sheet->getDelegate()->getHighestColumn();
        $event->sheet->getDelegate()->setAutoFilter("A1:{$highestColumn}1");
    }
}

// ── Meta sheet ────────────────────────────────────────────────────────────────

/**
 * @internal  Used only by ReportExcelExport — not part of the public API.
 */
final class MetaSheet implements FromCollection, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $definitionCode,
        private readonly string $definitionNameAr,
        private readonly array $scopeSummary,
        private readonly int $rowCount,
        private readonly string $locale,
        private readonly FormulaInjectionSanitizer $sanitizer,
    ) {}

    public function title(): string
    {
        return 'Meta';
    }

    public function collection(): Collection
    {
        $rows = [
            ['Report', $this->definitionNameAr.' ('.$this->definitionCode.')'],
            ['Generated at', now()->toDateTimeString()],
            ['Locale', $this->locale],
            ['Row count', $this->rowCount],
        ];

        foreach ($this->scopeSummary as $key => $value) {
            $rows[] = [
                ucwords(str_replace('_', ' ', $key)),
                is_array($value) ? implode(',', $value) : $value,
            ];
        }

        // Sanitize every metadata cell — filter values are user-supplied.
        return collect($rows)->map(fn (array $row): array => $this->sanitizer->sanitizeRow($row));
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            'A' => ['font' => ['bold' => true]],
        ];
    }
}
