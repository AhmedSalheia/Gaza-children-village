<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Mpdf\Http\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * mPDF HTTP client that rejects all outbound requests.
 *
 * Injected into the mPDF container via MpdfEngine::buildMpdf() to prevent
 * server-side request forgery (SSRF) during PDF generation.
 *
 * sanitizeHtml() strips all external URLs from template HTML at storage and
 * render time, so no absolute URL should reach mPDF's asset fetcher. This
 * client is a hard fail-safe: if any external URL somehow reaches mPDF's
 * HTTP layer (e.g. via a CSS url() value or an attribute value that bypassed
 * sanitization), the request is refused rather than fulfilled.
 *
 * The timeout-based approach (curlTimeout=0) is not reliable: libcurl treats
 * CURLOPT_CONNECTTIMEOUT=0 as "no limit", not "disabled", so it may still
 * make outbound connections. This implementation never calls cURL at all.
 */
final class DenyingHttpClient implements ClientInterface
{
    /**
     * @throws \RuntimeException Always — no outbound HTTP requests are permitted
     *                           during template PDF generation.
     */
    public function sendRequest(RequestInterface $request): never
    {
        $uri = (string) $request->getUri();

        throw new \RuntimeException(
            'mPDF attempted an outbound HTTP request during document rendering, which is not permitted. '
            ."URL: {$uri}. "
            .'External URLs must not appear in document templates. '
            .'Ensure all template src/href attributes are relative paths.',
        );
    }
}
