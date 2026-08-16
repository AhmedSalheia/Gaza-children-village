<?php

declare(strict_types=1);

namespace Modules\Attachments\Contracts;

/**
 * Value object returned by VirusScannerContract::scan().
 */
final class ScanResult
{
    public function __construct(
        /** true if the file is clean; false if a threat was detected. */
        public readonly bool $clean,
        /** Human-readable detail from the scanner (threat name, etc.). */
        public readonly ?string $detail = null,
    ) {}

    public static function clean(): self
    {
        return new self(clean: true);
    }

    public static function infected(string $detail): self
    {
        return new self(clean: false, detail: $detail);
    }
}
