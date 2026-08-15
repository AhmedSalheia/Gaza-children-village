<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Batch-save multiple attendance record updates on a draft or returned sheet.
 *
 * Delegates individual record validation to UpdateRecord so all business rules
 * (status code validity, reason requirement, time-field gating) are enforced
 * per-record. Returns the number of records successfully saved.
 *
 * @param list<array{
 *     enrollment_id: int,
 *     status_code: string,
 *     reason?: string|null,
 *     arrived_at?: string|null,
 *     departed_at?: string|null,
 *     safe_note?: string|null,
 * }> $entries
 */
final class SaveDraft
{
    public function __construct(
        private readonly UpdateRecord $updateRecord,
    ) {}

    public function __invoke(AttendanceSheet $sheet, array $entries): int
    {
        $sheetStatus = $sheet->status instanceof SheetStatus
            ? $sheet->status
            : SheetStatus::from((string) $sheet->status);

        if (! $sheetStatus->isEditable()) {
            throw new AttendanceException(
                "Sheet #{$sheet->id} is not editable (status: '{$sheetStatus->value}')."
            );
        }

        $saved = 0;

        foreach ($entries as $entry) {
            ($this->updateRecord)(
                sheet: $sheet,
                enrollmentId: (int) $entry['enrollment_id'],
                statusCode: (string) $entry['status_code'],
                reason: $entry['reason'] ?? null,
                arrivedAt: $entry['arrived_at'] ?? null,
                departedAt: $entry['departed_at'] ?? null,
                safeNote: $entry['safe_note'] ?? null,
            );

            $saved++;
        }

        return $saved;
    }
}
