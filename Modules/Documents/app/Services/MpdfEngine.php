<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Modules\Documents\Contracts\PdfEngineContract;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Container\SimpleContainer;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

/**
 * mPDF-backed implementation of PdfEngineContract.
 *
 * Chosen over dompdf and wkhtmltopdf because it is the only pure-PHP PDF
 * engine with production-grade Arabic shaping support (BiDi algorithm,
 * contextual glyph selection, RTL text direction) that runs without
 * system-level browser or headless Chrome dependencies.
 *
 * See docs/adr/F24-pdf-engine.md for the full decision record.
 *
 * Arabic support:
 *   - `autoScriptToLang = true`: mPDF detects Arabic Unicode ranges and
 *     attaches the correct language tag automatically.
 *   - `autoLangToFont = true`: the language tag selects an Arabic-capable
 *     font (DejaVuSans bundled with mPDF supports Basic Arabic + Extended).
 *   - `direction = 'rtl'`: sets the default paragraph direction for
 *     Arabic-locale documents.
 *
 * SSRF protection:
 *   - sanitizeHtml() in TemplatePlaceholderResolver strips all external URLs
 *     from template HTML (src, href, style url()) at storage time.
 *   - DenyingHttpClient is injected into mPDF's container so that any
 *     outbound HTTP request (regardless of how an external URL reached the
 *     render step) is rejected immediately with a RuntimeException rather
 *     than being fulfilled through cURL.
 *   - curlTimeout=0 would be CURLOPT_CONNECTTIMEOUT=0 which libcurl treats
 *     as "no limit" — we do NOT use that approach; DenyingHttpClient is the
 *     reliable guard.
 */
final class MpdfEngine implements PdfEngineContract
{
    public function generateFromHtml(string $html, array $options = []): string
    {
        $paper = $options['paper'] ?? 'A4';
        $direction = $options['direction'] ?? 'rtl';
        $headerHtml = $options['header_html'] ?? null;
        $footerHtml = $options['footer_html'] ?? null;

        try {
            $mpdf = $this->buildMpdf($paper, $direction);

            // Set per-page header/footer if configured for this template version.
            // These HTML fragments are sanitized at storage time by
            // DocumentTemplateVersionService::createDraft() and again in
            // renderPreviewPdf() before being passed here.
            if ($headerHtml !== null && $headerHtml !== '') {
                $mpdf->SetHTMLHeader($headerHtml);
            }

            if ($footerHtml !== null && $footerHtml !== '') {
                $mpdf->SetHTMLFooter($footerHtml);
            }

            $mpdf->WriteHTML($html);

            return $mpdf->Output('', 'S');
        } catch (MpdfException $e) {
            throw new \RuntimeException('PDF generation failed: '.$e->getMessage(), 0, $e);
        }
    }

    private function buildMpdf(string $paper, string $direction): Mpdf
    {
        // Extend mPDF's default font directories so custom Arabic fonts
        // placed in storage/fonts/ are discovered automatically.
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $defaultFontConfig = (new FontVariables)->getDefaults();

        $fontDirs = $defaultConfig['fontDir'];
        $fontDirs[] = storage_path('fonts');

        // Inject DenyingHttpClient into mPDF's service container.
        // mPDF's ServiceFactory checks the container for 'httpClient' before
        // constructing its default CurlHttpClient; providing it here means no
        // outbound HTTP request can be made during PDF generation.
        $container = new SimpleContainer(['httpClient' => new DenyingHttpClient]);

        return new Mpdf(
            [
                'mode' => 'utf-8',
                'format' => $paper,
                'default_font' => 'dejavusans',
                'fontDir' => $fontDirs,
                'fontdata' => $defaultFontConfig['fontdata'],
                'autoScriptToLang' => true, // detect Arabic script ranges automatically
                'autoLangToFont' => true, // assign appropriate font per language range
                'direction' => $direction,

                // Disable compression for development; enable in production via config.
                'compress' => (bool) config('documents.pdf_compress', true),

                // Temp directory for mPDF's internal use.
                'tempDir' => storage_path('app/mpdf_tmp'),
            ],
            $container,
        );
    }
}
