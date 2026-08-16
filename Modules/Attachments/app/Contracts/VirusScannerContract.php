<?php

declare(strict_types=1);

namespace Modules\Attachments\Contracts;

/**
 * Placeholder contract for a virus/malware scanner.
 *
 * Concrete implementations (e.g. ClamAV adapter) bind to this interface
 * via config('attachments.scanner') in AttachmentsServiceProvider.
 *
 * When no implementation is bound, SecureAttachmentService classifies every
 * upload as 'quarantine'. A scheduled job can later scan quarantined files
 * and update their status to 'available' or 'rejected'.
 */
interface VirusScannerContract
{
    /**
     * Scan the file at the given absolute filesystem path.
     *
     * @param  string  $absolutePath  Absolute path to the file on the local disk.
     */
    public function scan(string $absolutePath): ScanResult;
}
