<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public (unauthenticated) document verification endpoint.
 *
 * Route: GET /verify/{code}
 *
 * Rate limiting: 20 attempts per minute per IP to prevent brute-force
 * enumeration of verification codes.
 *
 * public_verification gate:
 *   Only document types with public_verification = true in the catalogue
 *   return a valid/cancelled status. Non-public types always return invalid,
 *   even when the code matches, to prevent information leakage about the
 *   existence of internally-issued documents.
 *
 * PII protection:
 *   The public response reveals ONLY: Valid/Invalid/Cancelled status,
 *   document type, document number, issuing institution name (bilingual),
 *   and issue date.
 *
 *   It NEVER reveals: student name, student ID, national ID, marks,
 *   guardian name, class group, file content, or any PII.
 *
 * The verification code lookup is done via the SHA-256 hash index
 * (verification_code_hash column), avoiding leaking the plain code in
 * slow DB queries. The supplied code is hashed before the query.
 */
final class DocumentVerificationController extends Controller
{
    public function __invoke(Request $request, string $code): \Illuminate\View\View
    {
        // Rate limiting: 20 requests per minute per IP
        $key     = 'verify:'.($request->ip() ?? 'unknown');
        $maxHits = 20;

        if (RateLimiter::tooManyAttempts($key, $maxHits)) {
            return view('document-verification', [
                'status'   => 'rate_limited',
                'document' => null,
            ]);
        }

        RateLimiter::hit($key, 60);

        // Sanitize and validate code length (must be 64 hex chars)
        $code = preg_replace('/[^a-f0-9]/i', '', $code) ?? '';

        if (strlen($code) !== 64) {
            return view('document-verification', [
                'status'   => 'invalid',
                'document' => null,
            ]);
        }

        // Hash the supplied code for the indexed lookup
        $hash = hash('sha256', $code);

        $document = DB::table('issued_documents as id')
            ->join('institutions as inst', 'inst.id', '=', 'id.institution_id')
            ->where('id.verification_code_hash', $hash)
            ->select(
                'id.document_number',
                'id.document_type_code',
                'id.locale',
                'id.issued_at',
                'id.cancelled_at',
                'inst.name_ar as institution_name_ar',
                'inst.name_en as institution_name_en',
            )
            ->first();

        if (! $document) {
            return view('document-verification', [
                'status'   => 'invalid',
                'document' => null,
            ]);
        }

        // ── public_verification gate ──────────────────────────────────────
        // Document types without public_verification = true must not expose
        // any metadata, even on a valid code match. Return invalid so that
        // the existence of the document cannot be inferred.
        $catalogueEntry = DB::table('document_type_catalogue')
            ->where('code', (string) $document->document_type_code)
            ->select('public_verification')
            ->first();

        if (! $catalogueEntry || ! $catalogueEntry->public_verification) {
            return view('document-verification', [
                'status'   => 'invalid',
                'document' => null,
            ]);
        }

        $status = $document->cancelled_at !== null ? 'cancelled' : 'valid';

        // Build the public-safe summary — no PII
        $summary = [
            'document_number'     => (string) $document->document_number,
            'document_type'       => (string) $document->document_type_code,
            'locale'              => (string) $document->locale,
            'issued_at'           => $document->issued_at ? substr((string) $document->issued_at, 0, 10) : null,
            'institution_name_ar' => (string) ($document->institution_name_ar ?? ''),
            'institution_name_en' => (string) ($document->institution_name_en ?? ''),
        ];

        return view('document-verification', [
            'status'   => $status,
            'document' => $summary,
        ]);
    }
}
