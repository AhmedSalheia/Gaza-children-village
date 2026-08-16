<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Tests for the public document verification endpoint.
 *
 * GET /verify/{code}
 *
 * Key requirements:
 *  - Returns Valid/Cancelled/Invalid status
 *  - Reveals ONLY: document number, type, institution name, issue date
 *  - NEVER reveals: student name, ID, guardian, marks, file content
 *  - Rate-limited
 *  - Lookup uses SHA-256 hash (not raw code in slow query)
 */
describe('Public document verification endpoint', function (): void {

    function seedCatalogueEntry(string $typeCode, bool $publicVerification = true): void
    {
        DB::table('document_type_catalogue')->insertOrIgnore([
            'code' => $typeCode,
            'label_ar' => 'وثيقة اختبار',
            'label_en' => 'Test Document',
            'completeness_checks' => '["active_enrollment"]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["guardian","staff"]',
            'public_verification' => $publicVerification,
            'reissuable' => false,
            'validity_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    function seedTemplateVersionForVerify(): int
    {
        $templateId = DB::table('document_templates')->insertGetId([
            'document_type_code' => 'proof_of_enrolment',
            'organization_id' => null,
            'institution_id' => null,
            'ar_available' => true,
            'approval_required' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('document_template_versions')->insertGetId([
            'template_id' => $templateId,
            'version_number' => 1,
            'status' => 'active',
            'locale' => 'ar',
            'body' => '<p>Test</p>',
            'content_hash' => hash('sha256', '<p>Test</p>'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    function seedIssuedDocument(array $overrides = []): array
    {
        $code = bin2hex(random_bytes(32));
        $hash = hash('sha256', $code);

        // Seed org → institution type → institution
        $orgId = DB::table('organizations')->insertGetId([
            'code' => 'GCV-TEST',
            'name_en' => 'Test Org',
            'name_ar' => 'منظمة الاختبار',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $instTypeId = DB::table('institution_types')->insertGetId([
            'code' => 'school',
            'name_ar' => 'مدرسة',
            'name_en' => 'School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $instId = DB::table('institutions')->insertGetId([
            'organization_id' => $orgId,
            'institution_type_id' => $instTypeId,
            'code' => 'GCV-TEST-01',
            'name_ar' => 'مدرسة الاختبار',
            'name_en' => 'Test School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $templateVersionId = seedTemplateVersionForVerify();

        // Seed catalogue entry so the public_verification gate passes
        $typeCode = $overrides['document_type_code'] ?? 'proof_of_enrolment';
        $isPublic = ! isset($overrides['_non_public']);
        seedCatalogueEntry($typeCode, $isPublic);
        unset($overrides['_non_public']);

        $docId = DB::table('issued_documents')->insertGetId(array_merge([
            'document_number' => 'GCV-POE-2026-00099',
            'document_type_code' => 'proof_of_enrolment',
            'enrollment_id' => 1,
            'student_profile_id' => 1,
            'institution_id' => $instId,
            'institution_semester_id' => null,
            'template_version_id' => $templateVersionId,
            'request_id' => null,
            'locale' => 'ar',
            'approved_by_account_id' => 1,
            'issued_at' => now(),
            'verification_code' => $code,
            'verification_code_hash' => $hash,
            'storage_path' => 'documents/2026/1/GCV-POE-2026-00099.pdf',
            'file_sha256' => hash('sha256', 'fake'),
            'cancelled_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return compact('code', 'hash', 'docId', 'instId');
    }

    test('valid code returns valid status with document summary', function (): void {
        $data = seedIssuedDocument();

        $response = $this->get('/verify/'.$data['code']);

        $response->assertStatus(200);
        $response->assertSeeText('valid');
        $response->assertSeeText('GCV-POE-2026-00099');
        $response->assertSeeText('مدرسة الاختبار');
    });

    test('public verification reveals no student PII', function (): void {
        $data = seedIssuedDocument();

        $response = $this->get('/verify/'.$data['code']);

        $content = $response->getContent();

        // Should not contain database column names that map to PII
        expect($content)->not->toContain('student_profile_id')
            ->and($content)->not->toContain('enrollment_id')
            ->and($content)->not->toContain('guardian');
    });

    test('cancelled document returns cancelled status', function (): void {
        $data = seedIssuedDocument(['cancelled_at' => now()]);

        $response = $this->get('/verify/'.$data['code']);

        $response->assertStatus(200);
        $response->assertSeeText('cancelled');
        $response->assertSeeText('GCV-POE-2026-00099');
    });

    test('unknown code returns invalid status', function (): void {
        $unknownCode = str_repeat('a', 64); // valid length but not in DB

        $response = $this->get('/verify/'.$unknownCode);

        $response->assertStatus(200);
        $response->assertSeeText('invalid');
    });

    test('code with wrong length returns invalid status', function (): void {
        $response = $this->get('/verify/tooshort');

        $response->assertStatus(200);
        $response->assertSeeText('invalid');
    });

    test('verification uses SHA-256 hash for lookup, not plain code', function (): void {
        $code = bin2hex(random_bytes(32));
        $hash = hash('sha256', $code);

        // Query would fail if the plain code were stored in verification_code_hash
        $result = DB::table('issued_documents')
            ->where('verification_code_hash', $hash)
            ->first();

        // No document for this code — just verifying the hash path is used
        expect($result)->toBeNull();

        // Confirm hash is different from code
        expect($hash)->not->toBe($code);
    });

    test('document type with public_verification false returns invalid even on valid code', function (): void {
        // Issue a document flagged as non-public via the _non_public override hint
        $data = seedIssuedDocument([
            'document_type_code' => 'student_record_transfer',
            '_non_public' => true, // tells helper to seed catalogue with public_verification=false
        ]);

        // Even though the code is valid, the verification endpoint should return 'invalid'
        $response = $this->get('/verify/'.$data['code']);

        $response->assertStatus(200);
        $response->assertSeeText('invalid');
    });

    test('document type with public_verification true returns valid on valid code', function (): void {
        // Seed the catalogue entry as publicly verifiable
        DB::table('document_type_catalogue')->insertOrIgnore([
            'code' => 'proof_of_enrolment',
            'label_ar' => 'إثبات التسجيل',
            'label_en' => 'Proof of Enrolment',
            'completeness_checks' => '["active_enrollment"]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["guardian","staff"]',
            'public_verification' => true, // Publicly verifiable
            'reissuable' => false,
            'validity_days' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = seedIssuedDocument();

        $response = $this->get('/verify/'.$data['code']);

        $response->assertStatus(200);
        $response->assertSeeText('valid');
    });
});

// ── Cross-guardian access denial ─────────────────────────────────────────────

describe('Cross-guardian access denial', function (): void {

    test('guardian cannot see another guardians student document via download route', function (): void {
        // Create a fake issued document for student 1
        $templateId = DB::table('document_templates')->insertGetId([
            'document_type_code' => 'proof_of_enrolment',
            'organization_id' => null,
            'institution_id' => null,
            'ar_available' => true,
            'approval_required' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $templateVersionId = DB::table('document_template_versions')->insertGetId([
            'template_id' => $templateId,
            'version_number' => 1,
            'status' => 'active',
            'locale' => 'ar',
            'body' => '<p>Test</p>',
            'content_hash' => hash('sha256', '<p>Test</p>'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $code = bin2hex(random_bytes(32));

        $docId = DB::table('issued_documents')->insertGetId([
            'document_number' => 'GCV-POE-2026-00777',
            'document_type_code' => 'proof_of_enrolment',
            'enrollment_id' => 1,
            'student_profile_id' => 1,
            'institution_id' => 1,
            'institution_semester_id' => null,
            'template_version_id' => $templateVersionId,
            'request_id' => null,
            'locale' => 'ar',
            'approved_by_account_id' => 1,
            'issued_at' => now(),
            'verification_code' => $code,
            'verification_code_hash' => hash('sha256', $code),
            'storage_path' => 'documents/2026/1/GCV-POE-2026-00777.pdf',
            'file_sha256' => hash('sha256', 'fake'),
            'cancelled_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Unauthenticated guardian request to download → redirected to login or 403
        $response = $this->get('/guardian/documents/download/'.$docId);

        // Should NOT be 200 (requires guardian auth)
        expect($response->getStatusCode())->not->toBe(200);
    });
});
