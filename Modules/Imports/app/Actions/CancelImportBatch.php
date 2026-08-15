<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Exceptions\BatchMutationDeniedException;
use Modules\Imports\Models\ImportBatch;

/**
 * Cancel an in-progress ImportBatch.
 *
 * Cancellation is allowed from any non-terminal status (uploaded, parsing,
 * ready_for_mapping, validating, ready_for_review). It is NOT allowed once
 * the batch is applying, completed, completed_with_errors, failed, or already
 * cancelled.
 *
 * @throws BatchMutationDeniedException if terminal.
 */
final class CancelImportBatch
{
    public function __invoke(ImportBatch $batch): ImportBatch
    {
        $batch->transitionTo(BatchStatus::Cancelled);

        return $batch;
    }
}
