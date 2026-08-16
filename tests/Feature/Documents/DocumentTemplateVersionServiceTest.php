<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Data\DocumentDataContext;
use Modules\Documents\Exceptions\TemplateActivationException;
use Modules\Documents\Exceptions\UnknownPlaceholderException;
use Modules\Documents\Models\DocumentTemplate;
use Modules\Documents\Services\DocumentTemplateVersionService;
use Modules\Documents\Services\TemplatePlaceholderResolver;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Fixture helper
// ---------------------------------------------------------------------------

function makeTemplate(): DocumentTemplate
{
    $t = new DocumentTemplate;
    $t->document_type_code = 'proof_of_enrolment';
    $t->organization_id = null;
    $t->institution_id = null;
    $t->ar_available = true;
    $t->en_available = false;
    $t->approval_required = false;
    $t->save();

    return $t;
}

// ---------------------------------------------------------------------------
// TemplatePlaceholderResolver: validation
// ---------------------------------------------------------------------------

describe('TemplatePlaceholderResolver validation', function (): void {

    it('accepts all catalogue-approved placeholder keys', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p>{{ student.full_name_ar }} — {{ institution.name_ar }}</p>';

        expect(fn () => $resolver->validateBody($body))->not->toThrow(Exception::class);
    });

    it('throws UnknownPlaceholderException for an unknown key', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p>{{ student.full_name_ar }} {{ student.national_id }}</p>';

        expect(fn () => $resolver->validateBody($body))
            ->toThrow(UnknownPlaceholderException::class);
    });

    it('includes the offending key in the exception', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p>{{ unknown.key }}</p>';

        try {
            $resolver->validateBody($body);
            $this->fail('Expected UnknownPlaceholderException');
        } catch (UnknownPlaceholderException $e) {
            expect($e->unknownKeys)->toBe(['unknown.key']);
        }
    });

    it('returns all placeholder keys found in the body', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '{{ student.full_name_ar }} {{ institution.name_ar }} {{ student.full_name_ar }}';

        $keys = $resolver->extractPlaceholders($body);

        // Unique — 'student.full_name_ar' appears twice but is returned once
        expect($keys)->toBe(['student.full_name_ar', 'institution.name_ar']);
    });

    it('resolves placeholders against a DocumentDataContext', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $ctx = DocumentDataContext::synthetic();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $result = $resolver->resolve($body, $ctx);

        expect($result)->toContain($ctx->studentFullNameAr);
        expect($result)->not->toContain('{{ student.full_name_ar }}');
    });

    it('produces a stable SHA-256 content hash', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '  <p>{{ student.full_name_ar }}</p>  ';

        $hash1 = $resolver->contentHash($body);
        $hash2 = $resolver->contentHash($body);

        expect($hash1)->toMatch('/^[0-9a-f]{64}$/')->and($hash2)->toBe($hash1);
    });

});

// ---------------------------------------------------------------------------
// DocumentTemplateVersionService: version creation
// ---------------------------------------------------------------------------

describe('DocumentTemplateVersionService: version creation', function (): void {

    it('creates a draft version with version_number = 1 for a new template', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft(
            template: $template,
            locale: 'ar',
            body: $body,
        );

        expect($version->version_number)->toBe(1)
            ->and($version->status)->toBe('draft')
            ->and($version->content_hash)->toBeNull(); // set on activation
    });

    it('assigns distinct version numbers to two sequential createDraft() calls (allocation atomicity)', function (): void {
        // Regression: the version-number lock and INSERT must happen in one transaction.
        // If they are split, two simultaneous calls can compute the same max+1 then
        // both attempt to INSERT with the same (template_id, version_number), causing
        // a unique-constraint failure on one request.
        //
        // The current fix: lockForUpdate + max-read + INSERT are all inside one DB
        // transaction. A second concurrent call blocks on the lock and sees the
        // committed INSERT before computing its own max+1.
        //
        // Note: true concurrent-connection testing is not possible with SQLite in-memory
        // databases (which are per-connection and single-process). This sequential test
        // verifies the allocation contract by confirming two calls always produce
        // distinct, monotonically increasing version numbers. The transaction lock
        // provides the guarantee under real MySQL/PgSQL concurrency.
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';
        $svc = app(DocumentTemplateVersionService::class);

        $v1 = $svc->createDraft($template, 'ar', $body);
        $v2 = $svc->createDraft($template, 'ar', $body);

        expect($v1->version_number)->toBe(1);
        expect($v2->version_number)->toBe(2);
        expect($v1->id)->not->toBe($v2->id);
    });

    it('increments version_number for subsequent drafts', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        $v2 = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);

        expect($v2->version_number)->toBe(2);
    });

    it('rejects a draft body with unknown placeholders at creation time', function (): void {
        $template = makeTemplate();

        expect(fn () => app(DocumentTemplateVersionService::class)->createDraft(
            template: $template,
            locale: 'ar',
            body: '<p>{{ student.national_id }}</p>',
        ))->toThrow(UnknownPlaceholderException::class);
    });

    it('stores the placeholder catalogue on the version', function (): void {
        $template = makeTemplate();
        $body = '{{ student.full_name_ar }} {{ institution.name_ar }}';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);

        expect($version->placeholder_catalogue)
            ->toContain('student.full_name_ar')
            ->toContain('institution.name_ar');
    });

});

// ---------------------------------------------------------------------------
// DocumentTemplateVersionService: activation
// ---------------------------------------------------------------------------

describe('DocumentTemplateVersionService: activation (version immutability)', function (): void {

    it('activates a draft version and sets content_hash', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        $activated = app(DocumentTemplateVersionService::class)->activate($version, actorAccountId: 1);

        expect($activated->status)->toBe('active')
            ->and($activated->content_hash)->toMatch('/^[0-9a-f]{64}$/')
            ->and($activated->approver_account_id)->toBe(1);
    });

    it('updates template.active_version_id after activation', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        app(DocumentTemplateVersionService::class)->activate($version, actorAccountId: 1);

        $template->refresh();
        expect($template->active_version_id)->toBe($version->id);
    });

    it('archives the previously active version when a new one is activated', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $v1 = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        app(DocumentTemplateVersionService::class)->activate($v1, actorAccountId: 1);

        $v2 = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        app(DocumentTemplateVersionService::class)->activate($v2, actorAccountId: 1);

        expect($v1->fresh()->status)->toBe('archived');
        expect($v2->fresh()->status)->toBe('active');
    });

    it('throws TemplateActivationException when activating a non-draft version', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        app(DocumentTemplateVersionService::class)->activate($version, actorAccountId: 1);

        // Attempting to activate the same version again must fail
        expect(fn () => app(DocumentTemplateVersionService::class)->activate($version->fresh(), actorAccountId: 1))
            ->toThrow(TemplateActivationException::class);
    });

    it('rejects activation of a version that was archived by a concurrent activation (stale-model guard)', function (): void {
        // Regression: activate() previously checked isDraft() on the caller's model
        // before opening a transaction, creating a TOCTOU window. A request that read
        // a draft could be delayed while another request activated+archived it. When
        // the delayed request entered its transaction, it would write the stale draft
        // instance back as 'active', reviving an archived version.
        //
        // Fix: activate() now locks and reloads the version inside the transaction
        // (lockForUpdate) and asserts isDraft() on the fresh locked row.
        //
        // Test: simulate the stale-model scenario by activating a version (which
        // archives an earlier version), then attempting to re-activate the archived
        // version using a stale model that still shows status='draft'.
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';
        $svc = app(DocumentTemplateVersionService::class);

        // Create draft v1 and activate it — it becomes active
        $v1 = $svc->createDraft($template, 'ar', $body);
        $svc->activate($v1, actorAccountId: 1);

        // Create draft v2 and activate it — v1 is now archived
        $v2 = $svc->createDraft($template, 'ar', $body);
        $svc->activate($v2, actorAccountId: 1);

        // v1 is archived in the DB; simulate a stale model by manually resetting
        // its in-memory status back to 'draft' (as if loaded before the archive happened)
        $v1->status = 'draft';

        // The activation must reject the stale model: the transaction re-reads the
        // DB row and sees it is archived, not draft, and throws TemplateActivationException.
        expect(fn () => $svc->activate($v1, actorAccountId: 1))
            ->toThrow(TemplateActivationException::class);

        // Confirm v1 is still archived in the DB — the stale activation did not revive it
        expect($v1->fresh()->status)->toBe('archived');
    });

    it('throws LogicException when assertIsDraft is called on an active version', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        app(DocumentTemplateVersionService::class)->activate($version, actorAccountId: 1);

        expect(fn () => $version->fresh()->assertIsDraft())
            ->toThrow(LogicException::class);
    });

    it('writes an audit event on activation', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        app(DocumentTemplateVersionService::class)->activate($version, actorAccountId: 42);

        $event = DB::table('audit_events')
            ->where('action', 'document_template.activated')
            ->where('actor_account_id', 42)
            ->first();

        expect($event)->not->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// DocumentTemplateVersionService: preview rendering
// ---------------------------------------------------------------------------

describe('DocumentTemplateVersionService: preview rendering', function (): void {

    it('renders preview HTML with synthetic data without touching real records', function (): void {
        $template = makeTemplate();
        $body = '<p dir="rtl">{{ student.full_name_ar }} — {{ institution.name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft($template, 'ar', $body);
        $html = app(DocumentTemplateVersionService::class)->renderPreviewHtml($version);

        $ctx = DocumentDataContext::synthetic();
        expect($html)
            ->toContain($ctx->studentFullNameAr)
            ->toContain($ctx->institutionNameAr)
            ->not->toContain('{{ student.full_name_ar }}');
    });

});
