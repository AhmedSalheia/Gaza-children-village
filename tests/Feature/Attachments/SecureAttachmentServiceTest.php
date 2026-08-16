<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Attachments\Contracts\ScanResult;
use Modules\Attachments\Contracts\VirusScannerContract;
use Modules\Attachments\Data\UploaderContext;
use Modules\Attachments\Exceptions\AttachmentException;
use Modules\Attachments\Models\AttachmentLink;
use Modules\Attachments\Models\SecureAttachment;
use Modules\Attachments\Services\SecureAttachmentService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function uploaderContext(int $institutionId = 1): UploaderContext
{
    return new UploaderContext(
        actorType: 'administrative',
        accountId: 1,
        portal: 'admin',
        institutionId: $institutionId,
    );
}

function makeAttachmentService(?VirusScannerContract $scanner = null): SecureAttachmentService
{
    return new SecureAttachmentService($scanner);
}

function fakeAttachmentsDisk(): void
{
    Storage::fake('attachments');
    config(['attachments.disk' => 'attachments']);
}

/**
 * Create a real UploadedFile containing minimal PDF bytes so that
 * finfo(FILEINFO_MIME_TYPE) detects it as 'application/pdf'.
 * UploadedFile::fake()->create() produces an empty file detected as 'application/x-empty'.
 */
function fakePdf(string $name = 'evidence.pdf'): UploadedFile
{
    $minimalPdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\nxref\n%%EOF";
    $tmpPath = tempnam(sys_get_temp_dir(), 'gcvtest_pdf_');
    file_put_contents($tmpPath, $minimalPdf);

    return new UploadedFile($tmpPath, $name, 'application/pdf', null, true);
}

/**
 * A scanner stub that always reports the file as clean.
 */
function cleanScanner(): VirusScannerContract
{
    return new class implements VirusScannerContract
    {
        public function scan(string $absolutePath): ScanResult
        {
            return ScanResult::clean();
        }
    };
}

/**
 * A scanner stub that always reports the file as infected.
 */
function infectedScanner(?string $detail = 'EICAR-Test-File'): VirusScannerContract
{
    return new class($detail) implements VirusScannerContract
    {
        public function __construct(private readonly ?string $detail) {}

        public function scan(string $absolutePath): ScanResult
        {
            return ScanResult::infected($this->detail ?? 'EICAR-Test-File');
        }
    };
}

// ---------------------------------------------------------------------------
// Upload validation — accepted formats
// ---------------------------------------------------------------------------

describe('SecureAttachmentService upload validation', function (): void {

    beforeEach(fn () => fakeAttachmentsDisk());

    it('stores a valid PDF and returns a SecureAttachment record', function (): void {
        $file = fakePdf();
        $result = makeAttachmentService()->store($file, uploaderContext());

        expect($result)->toBeInstanceOf(SecureAttachment::class)
            ->and($result->mime_type)->toBe('application/pdf')
            ->and($result->extension)->toBe('pdf')
            ->and($result->status)->toBe('available')
            ->and($result->institution_id)->toBe(1)
            ->and($result->uploader_portal)->toBe('admin');

        expect(Storage::disk('attachments')->exists($result->storage_path))->toBeTrue();
    });

    it('stores a valid JPEG and returns a SecureAttachment record', function (): void {
        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $result = makeAttachmentService()->store($file, uploaderContext());

        expect($result->extension)->toBeIn(['jpg', 'jpeg'])
            ->and($result->status)->toBe('available');
    });

    it('stores a valid PNG and returns a SecureAttachment record', function (): void {
        $file = UploadedFile::fake()->image('badge.png', 100, 100);
        $result = makeAttachmentService()->store($file, uploaderContext());

        expect($result->extension)->toBe('png')
            ->and($result->status)->toBe('available');
    });

    it('records the correct SHA-256 hash', function (): void {
        $file = fakePdf('doc.pdf');
        $result = makeAttachmentService()->store($file, uploaderContext());

        $expected = hash_file('sha256', $file->getRealPath());
        expect($result->sha256_hash)->toBe($expected);
    });

    it('storage filename is UUID-based — never derived from original filename', function (): void {
        $file = fakePdf('../../../etc/passwd.pdf');
        $result = makeAttachmentService()->store($file, uploaderContext());

        expect($result->storage_filename)->toMatch('/^[0-9a-f\-]{36}\.[a-z]+$/');
        expect($result->storage_path)->not->toContain('..');
        expect($result->storage_path)->not->toContain('etc/passwd');
    });

    it('original filename is sanitized for display (path traversal in display name prevented)', function (): void {
        $file = fakePdf('../../../secret.pdf');
        $result = makeAttachmentService()->store($file, uploaderContext());

        expect($result->original_filename)->not->toContain('..');
        expect($result->original_filename)->not->toContain('/');
    });

    // -----------------------------------------------------------------------
    // Rejection: executable / script formats
    // -----------------------------------------------------------------------

    it('rejects a .exe file', function (): void {
        $file = UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream');

        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, '.exe');
    });

    it('rejects a .php file', function (): void {
        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, '.php');
    });

    it('rejects a .html file', function (): void {
        $file = UploadedFile::fake()->create('xss.html', 10, 'text/html');

        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, '.html');
    });

    it('rejects a .sh file', function (): void {
        $file = UploadedFile::fake()->create('backdoor.sh', 10, 'application/x-sh');

        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, '.sh');
    });

    it('rejects a file whose extension is not in the allowlist', function (): void {
        $file = UploadedFile::fake()->create('letter.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, 'docx');
    });

    it('rejects a file that exceeds the maximum size', function (): void {
        $maxMb = (int) round(config('attachments.max_size_bytes', 10485760) / 1024 / 1024);
        $file = UploadedFile::fake()->create('large.pdf', ($maxMb + 1) * 1024, 'application/pdf');

        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, 'maximum allowed size');
    });

    // -----------------------------------------------------------------------
    // Virus scanner — synchronous path
    // -----------------------------------------------------------------------

    it('marks upload as available when a configured scanner reports the file is clean', function (): void {
        $file = fakePdf();
        $result = makeAttachmentService(cleanScanner())->store($file, uploaderContext());

        expect($result->status)->toBe('available');
    });

    it('persists a rejected record before purging the blob when a scanner flags the file', function (): void {
        $file = fakePdf();

        expect(fn () => makeAttachmentService(infectedScanner())->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, 'security threat');

        // The rejected record must persist even though the exception was thrown.
        // This is the forensic audit trail for the rejected upload.
        $rejected = SecureAttachment::where('status', 'rejected')->first();
        expect($rejected)->not->toBeNull()
            ->and($rejected->mime_type)->toBe('application/pdf')
            ->and($rejected->institution_id)->toBe(1);

        // The blob must have been purged from disk.
        expect(Storage::disk('attachments')->exists($rejected->storage_path))->toBeFalse();
    });

    it('emits an attachment.rejected audit event when a scanner flags the file', function (): void {
        $file = fakePdf();

        try {
            makeAttachmentService(infectedScanner('EICAR-Test-File'))->store($file, uploaderContext());
        } catch (AttachmentException) {
            // Expected
        }

        $event = DB::table('audit_events')
            ->where('action', 'attachment.rejected')
            ->where('source_module', 'Attachments')
            ->first();

        expect($event)->not->toBeNull()
            ->and($event->actor_type)->toBe('administrative');
    });

    it('prevents re-upload of a file that was previously rejected by the scanner', function (): void {
        $file = fakePdf();

        // First upload is rejected
        try {
            makeAttachmentService(infectedScanner())->store($file, uploaderContext());
        } catch (AttachmentException) {
        }

        // Second upload of same content → pre-check finds the rejected row → deny
        expect(fn () => makeAttachmentService()->store($file, uploaderContext()))
            ->toThrow(AttachmentException::class, 'previously rejected');
    });

    it('classifies uploads as available immediately when no scanner is configured', function (): void {
        // No scanner → validation pipeline is the gate → available immediately.
        // 'quarantine' is reserved for async scanning architectures (not currently implemented).
        config(['attachments.scanner' => null]);
        $file = fakePdf();
        $result = makeAttachmentService()->store($file, uploaderContext());

        expect($result->status)->toBe('available');
    });

    // -----------------------------------------------------------------------
    // Duplicate detection (institution-scoped; unique constraint + pre-check)
    // -----------------------------------------------------------------------

    it('returns the existing attachment instead of creating a duplicate within the same institution', function (): void {
        $file = fakePdf();

        $first = makeAttachmentService()->store($file, uploaderContext(institutionId: 5));
        $second = makeAttachmentService()->store($file, uploaderContext(institutionId: 5));

        expect($first->id)->toBe($second->id)
            ->and(SecureAttachment::count())->toBe(1);
    });

    it('allows the same file hash in different institutions (no cross-institution leakage)', function (): void {
        $file = fakePdf();

        $inst1 = makeAttachmentService()->store($file, uploaderContext(institutionId: 1));
        $inst2 = makeAttachmentService()->store($file, uploaderContext(institutionId: 2));

        expect($inst1->id)->not->toBe($inst2->id)
            ->and(SecureAttachment::count())->toBe(2);
    });

    it('handles a race-condition duplicate via unique constraint recovery', function (): void {
        $file = fakePdf();

        // Simulate the winner committing its row first
        $winner = makeAttachmentService()->store($file, uploaderContext(institutionId: 1));

        // The loser uploads the same file; the pre-check now finds the winner's row
        // and returns it without creating a second file.
        $loser = makeAttachmentService()->store($file, uploaderContext(institutionId: 1));

        expect($loser->id)->toBe($winner->id)
            ->and(SecureAttachment::count())->toBe(1)
            // Only one copy of the file on disk
            ->and(Storage::disk('attachments')->exists($winner->storage_path))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // Attachment linking
    // -----------------------------------------------------------------------

    it('creates a link between an attachment and a domain entity', function (): void {
        $file = fakePdf('proof.pdf');
        $attachment = makeAttachmentService()->store($file, uploaderContext());

        $link = makeAttachmentService()->link($attachment, 'CorrectionRequest', 42, 'supporting_evidence');

        expect($link->attachment_id)->toBe($attachment->id)
            ->and($link->linkable_type)->toBe('CorrectionRequest')
            ->and($link->linkable_id)->toBe(42)
            ->and($link->link_type)->toBe('supporting_evidence');
    });

    it('link() is idempotent — returns the existing link on repeat calls', function (): void {
        $file = fakePdf('proof.pdf');
        $attachment = makeAttachmentService()->store($file, uploaderContext());

        $link1 = makeAttachmentService()->link($attachment, 'CorrectionRequest', 42);
        $link2 = makeAttachmentService()->link($attachment, 'CorrectionRequest', 42);

        expect($link1->id)->toBe($link2->id)
            ->and(AttachmentLink::count())->toBe(1);
    });
});
