<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Models\ImportBatch;
use Modules\Imports\Models\ImportFile;

/**
 * Store an uploaded file securely and register an ImportBatch.
 *
 * The file is placed in the private `imports` disk (never the public disk).
 * A SHA-256 checksum is computed for integrity verification.
 * The batch starts in `uploaded` status; no parsing occurs here.
 */
final class UploadImportFile
{
    /**
     * @return array{batch: ImportBatch, file: ImportFile}
     */
    public function __invoke(
        UploadedFile|string $source,
        int $actorAccountId,
        int $institutionId,
        ?int $operationalPeriodId = null,
        ?string $notes = null,
        string $disk = 'local',
    ): array {
        // Resolve file path and metadata.
        if ($source instanceof UploadedFile) {
            $originalName = $source->getClientOriginalName();
            $mimeType = $source->getMimeType() ?? 'application/octet-stream';
            $fileSize = $source->getSize();
            $tempPath = $source->getRealPath();
        } else {
            $originalName = basename($source);
            $mimeType = mime_content_type($source) ?: 'application/octet-stream';
            $fileSize = filesize($source);
            $tempPath = $source;
        }

        // Compute SHA-256 before moving.
        $sha256 = hash_file('sha256', $tempPath);

        // Determine extension and storage path.
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $storagePath = 'imports/'.date('Y/m/d').'/'.uniqid('import_', true).'.'.$ext;

        // Store the file.
        if ($source instanceof UploadedFile) {
            $source->storeAs(dirname($storagePath), basename($storagePath), ['disk' => $disk]);
        } else {
            Storage::disk($disk)->put($storagePath, file_get_contents($tempPath));
        }

        // Create ImportBatch.
        $batch = new ImportBatch;
        $batch->status = BatchStatus::Uploaded;
        $batch->actor_account_id = $actorAccountId;
        $batch->institution_id = $institutionId;
        $batch->operational_period_id = $operationalPeriodId;
        $batch->original_filename = $originalName;
        $batch->mime_type = $mimeType;
        $batch->file_size_bytes = $fileSize;
        $batch->notes = $notes;
        $batch->save();

        // Create ImportFile.
        $file = new ImportFile;
        $file->batch_id = $batch->id;
        $file->storage_path = $storagePath;
        $file->content_sha256 = $sha256;
        $file->save();

        return ['batch' => $batch, 'file' => $file];
    }
}
