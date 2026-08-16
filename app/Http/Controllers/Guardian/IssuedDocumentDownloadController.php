<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Models\IssuedDocument;

/**
 * Streams a private issued-document PDF to an authenticated guardian.
 *
 * Authorization:
 *   - Guardian must be authenticated.
 *   - The document must have been requested by this guardian account
 *     OR the guardian must have a portal-eligible relationship with the student.
 *   - Cancelled documents are blocked for guardian download.
 *
 * Security: same as other download controllers.
 */
final class IssuedDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, int $documentId): Response
    {
        $guardianAccount = auth('guardian')->user();

        if (! $guardianAccount) {
            abort(403);
        }

        $document = IssuedDocument::findOrFail($documentId);

        if ($document->isCancelled()) {
            abort(403, 'This document has been cancelled and cannot be downloaded.');
        }

        // Authorization: guardian must own the request OR have a portal-eligible
        // relationship with the student at the time of download.
        $this->authorizeGuardianAccess($guardianAccount, $document);

        return $this->streamDocument($document);
    }

    private function authorizeGuardianAccess(object $guardianAccount, IssuedDocument $document): void
    {
        // Resolve guardian profile
        $profile = DB::table('guardian_profiles')
            ->where('guardian_account_id', (int) $guardianAccount->getKey())
            ->select('id')
            ->first();

        if (! $profile) {
            abort(403, 'No guardian profile linked to this account.');
        }

        // Check portal-eligible relationship with the student
        $hasRelationship = DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', (int) $profile->id)
            ->where('student_profile_id', (int) $document->student_profile_id)
            ->where('verification_status', 'verified')
            ->where('portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', now()->toDateString()))
            ->exists();

        if (! $hasRelationship) {
            abort(403, 'You do not have access to this student\'s documents.');
        }
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
