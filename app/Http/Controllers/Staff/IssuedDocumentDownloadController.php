<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Models\IssuedDocument;

/**
 * Streams a private issued-document PDF to an authenticated staff member.
 *
 * Authorization:
 *   - Staff must be authenticated.
 *   - The document's institution_id AND institution_semester_id must both
 *     match the staff member's trusted active position scope — the same
 *     two-column scope contract used by all other staff portal read paths.
 *
 * Security: private disk, SHA-256 integrity check, sanitized filename.
 */
final class IssuedDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, int $documentId): Response
    {
        $staffAccount = auth('staff')->user();

        if (! $staffAccount) {
            abort(403);
        }

        $document = IssuedDocument::findOrFail($documentId);

        // ── Resolve the staff member's current trusted scope ─────────────
        // We look for an active position that matches both institution_id
        // and institution_semester_id on the document — the same scope
        // check that DocumentReview and DocumentReviewQueue apply.
        $position = DB::table('staff_positions')
            ->where('staff_profile_id', $staffAccount->staff_profile_id)
            ->where('started_on', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', now()->toDateString()))
            ->where('institution_id', (int) $document->institution_id)
            ->first();

        if (! $position) {
            abort(403, 'Document is not in your assigned institution scope.');
        }

        // ── Semester scope check ─────────────────────────────────────────
        // If the document carries an institution_semester_id, the staff
        // member's active position must be in the same semester.
        if ($document->institution_semester_id !== null) {
            if ((int) $position->institution_semester_id !== (int) $document->institution_semester_id) {
                abort(403, 'Document is not in your assigned semester scope.');
            }
        }

        return $this->streamDocument($document);
    }

    private function streamDocument(IssuedDocument $document): Response
    {
        $disk = Storage::disk('private');

        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found in storage.');
        }

        $content = $disk->get($document->storage_path);

        if (hash('sha256', $content) !== $document->file_sha256) {
            abort(500, 'Document file integrity check failed.');
        }

        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $document->document_number).'.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }
}
