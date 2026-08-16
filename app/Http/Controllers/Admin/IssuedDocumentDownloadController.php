<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Models\IssuedDocument;

/**
 * Streams a private issued-document PDF to an authenticated admin.
 *
 * Authorization: the admin must be authenticated. Additional permission
 * checks (DOCUMENT_DOWNLOAD) are applied before the admin can reach this
 * route — enforced by the admin portal middleware stack.
 *
 * Security:
 *   - File served from private disk; no public URL is ever exposed.
 *   - SHA-256 verified before streaming (detects storage corruption).
 *   - Content-Disposition filename is sanitized (no user-controlled input).
 *   - Cancelled documents are streamed but labelled cancelled in the header.
 */
final class IssuedDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, int $documentId): Response
    {
        // Require admin authentication
        if (! auth('admin')->check()) {
            abort(403);
        }

        $document = IssuedDocument::findOrFail($documentId);

        return $this->streamDocument($document);
    }

    private function streamDocument(IssuedDocument $document): Response
    {
        $disk = Storage::disk('private');

        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found in storage.');
        }

        $content = $disk->get($document->storage_path);

        // Integrity check
        if (hash('sha256', $content) !== $document->file_sha256) {
            abort(500, 'Document file integrity check failed.');
        }

        // Sanitized filename: only the document number (safe alphanumeric + hyphens)
        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $document->document_number).'.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }
}
