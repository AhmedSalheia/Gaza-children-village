<?php

declare(strict_types=1);

namespace Modules\Documents\Contracts;

/**
 * PDF generation engine contract.
 *
 * Callers supply fully-composed HTML (UTF-8, with embedded CSS).
 * The engine returns the raw PDF binary string suitable for streaming
 * or writing to disk.
 *
 * Implementations are responsible for:
 *   - Correct Unicode rendering (including Arabic shaping and RTL text direction).
 *   - Embedded font subsetting.
 *   - Pagination, headers, and footers as specified in `$options`.
 *
 * Option keys recognised by all implementations (others are engine-specific):
 *   - `paper`      (string)  — paper size, default 'A4'
 *   - `direction`  (string)  — 'rtl' | 'ltr', default 'rtl'
 *   - `header_html` (string) — HTML fragment for page header
 *   - `footer_html` (string) — HTML fragment for page footer
 */
interface PdfEngineContract
{
    /**
     * Generate a PDF from a UTF-8 HTML string.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws \RuntimeException When PDF generation fails
     */
    public function generateFromHtml(string $html, array $options = []): string;
}
