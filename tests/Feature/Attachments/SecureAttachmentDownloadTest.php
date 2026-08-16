<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\StaffAccount;
use Modules\Attachments\Data\UploaderContext;
use Modules\Attachments\Models\SecureAttachment;
use Modules\Attachments\Services\SecureAttachmentService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function dlUploaderContext(int $institutionId = 1): UploaderContext
{
    return new UploaderContext(
        actorType: 'administrative',
        accountId: 1,
        portal: 'admin',
        institutionId: $institutionId,
    );
}

/**
 * Create a SecureAttachment row with a real file on the fake disk, bypassing
 * the full validation pipeline for controller-layer test isolation.
 */
function createAvailableAttachment(int $institutionId = 1, string $status = 'available'): SecureAttachment
{
    Storage::fake('attachments');
    config(['attachments.disk' => 'attachments']);

    $uuid = Str::uuid()->toString();
    $path = "institution-{$institutionId}/evidence/{$uuid}.pdf";

    Storage::disk('attachments')->put($path, '%PDF-1.4 fake content');

    $attachment = new SecureAttachment;
    $attachment->id = $uuid;
    $attachment->original_filename = 'evidence.pdf';
    $attachment->storage_filename = "{$uuid}.pdf";
    $attachment->mime_type = 'application/pdf';
    $attachment->extension = 'pdf';
    $attachment->size_bytes = 22;
    $attachment->sha256_hash = hash('sha256', '%PDF-1.4 fake content');
    $attachment->storage_disk = 'attachments';
    $attachment->storage_path = $path;
    $attachment->uploader_actor_type = 'administrative';
    $attachment->uploader_account_id = 1;
    $attachment->uploader_portal = 'admin';
    $attachment->institution_id = $institutionId;
    $attachment->classification = 'evidence';
    $attachment->status = $status;
    $attachment->save();

    return $attachment;
}

/**
 * Grant a permission key to an administrative account via the minimal
 * role → permission → grant chain that PolicyKernel resolves.
 *
 * Schema: permissions(key, description, group), roles(code, label),
 *         administrative_account_roles(administrative_account_id, role_id, granted_by, revoked_at).
 */
function grantAdminPermission(int $adminAccountId, string $permissionKey, ?string $revokedAt = null): void
{
    $roleCode = 'test_role_'.substr(md5($permissionKey.$adminAccountId.uniqid()), 0, 8);

    $permissionId = DB::table('permissions')->insertGetId([
        'key' => $permissionKey,
        'description' => $permissionKey,
        'group' => 'attachment',
    ]);

    $roleId = DB::table('roles')->insertGetId([
        'code' => $roleCode,
        'label' => $roleCode,
    ]);

    DB::table('role_permissions')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
    ]);

    DB::table('administrative_account_roles')->insert([
        'administrative_account_id' => $adminAccountId,
        'role_id' => $roleId,
        'granted_by' => 'test-seeder',
        'revoked_at' => $revokedAt,
    ]);
}

// ---------------------------------------------------------------------------
// Admin portal — authentication guards
// ---------------------------------------------------------------------------

describe('Admin download controller — authentication guards', function (): void {

    it('returns 403 or redirect when no admin session is active', function (): void {
        $attachment = createAvailableAttachment();

        $response = $this->get(route('admin.attachments.download', $attachment->id));

        expect($response->status())->toBeIn([302, 403]);
    });

    it('returns 404 when attachment UUID does not exist', function (): void {
        // active() so the request reaches the 404 check (not rejected for pending status)
        $admin = AdministrativeAccount::factory()->active()->create();
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attachments.download', 'nonexistent-uuid'));

        expect($response->status())->toBe(404);
    });

    it('returns 403 for a quarantined attachment (not yet scanned)', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1, status: 'quarantine');

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attachments.download', $attachment->id));

        expect($response->status())->toBe(403);
    });

});

// ---------------------------------------------------------------------------
// Admin portal — authorization (role codes + PolicyKernel)
// ---------------------------------------------------------------------------

describe('Admin download controller — authorization', function (): void {

    it('serves the file when admin holds attachment.read permission', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1);

        grantAdminPermission($admin->id, 'attachment.read');

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attachments.download', $attachment->id));

        $response->assertOk();
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    });

    it('returns 403 when admin has no roles granting attachment.read', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1);

        // No role grants at all — PolicyKernel must deny at step 6 (empty role codes)
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attachments.download', $attachment->id));

        expect($response->status())->toBe(403);
    });

    it('returns 403 when admin role grant is revoked', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1);

        // Grant with a past revocation timestamp — controller filters these out
        grantAdminPermission($admin->id, 'attachment.read', revokedAt: now()->toDateTimeString());

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attachments.download', $attachment->id));

        expect($response->status())->toBe(403);
    });

    it('returns 403 for a suspended admin account', function (): void {
        $admin = AdministrativeAccount::factory()->suspended()->create();
        $attachment = createAvailableAttachment(institutionId: 1);

        grantAdminPermission($admin->id, 'attachment.read');

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attachments.download', $attachment->id));

        // PolicyKernel step 2 must deny suspended accounts regardless of role codes
        expect($response->status())->toBe(403);
    });

});

// ---------------------------------------------------------------------------
// Staff portal — authentication guards
// ---------------------------------------------------------------------------

describe('Staff download controller — authentication guards', function (): void {

    it('returns 403 or redirect when no staff session is active', function (): void {
        $attachment = createAvailableAttachment();

        $response = $this->get(route('staff.attachments.download', $attachment->id));

        expect($response->status())->toBeIn([302, 403]);
    });

    it('returns 404 when attachment UUID does not exist', function (): void {
        $staff = StaffAccount::factory()->active()->create();
        $this->actingAs($staff, 'staff');

        $response = $this->get(route('staff.attachments.download', 'nonexistent-uuid'));

        expect($response->status())->toBe(404);
    });

    it('returns 403 for a quarantined attachment', function (): void {
        $staff = StaffAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1, status: 'quarantine');

        $this->actingAs($staff, 'staff');

        $response = $this->get(route('staff.attachments.download', $attachment->id));

        expect($response->status())->toBe(403);
    });

    it('denies an active staff account with no profile/position (empty role codes → PolicyKernel deny)', function (): void {
        // active() so we reach authorization; no staff_profile_id → no position → empty role codes
        $staff = StaffAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1);

        $this->actingAs($staff, 'staff');

        $response = $this->get(route('staff.attachments.download', $attachment->id));

        expect($response->status())->toBe(403);
    });

});

// ---------------------------------------------------------------------------
// End-to-end: store → download (proves the full workflow is operational)
// ---------------------------------------------------------------------------

describe('End-to-end store-to-download', function (): void {

    it('a file uploaded via SecureAttachmentService can be downloaded immediately when no scanner is configured', function (): void {
        Storage::fake('attachments');
        config(['attachments.disk' => 'attachments', 'attachments.scanner' => null]);

        // Upload through the service — no scanner → status = available immediately
        $svc = new SecureAttachmentService;
        $file = fakePdf('evidence.pdf');
        $attachment = $svc->store($file, dlUploaderContext(institutionId: 1));

        expect($attachment->status)->toBe('available');

        // Authorized admin downloads it — proves the full path is working
        $admin = AdministrativeAccount::factory()->active()->create();
        grantAdminPermission($admin->id, 'attachment.read');

        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attachments.download', $attachment->id));

        $response->assertOk();
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    });

});

// ---------------------------------------------------------------------------
// Cross-institution isolation (service-level enforcement)
// ---------------------------------------------------------------------------

describe('Cross-institution access isolation', function (): void {

    it('scopeForInstitution filters attachments by institution_id', function (): void {
        $att1 = createAvailableAttachment(institutionId: 1);
        $att2 = createAvailableAttachment(institutionId: 2);

        $inst1Ids = SecureAttachment::forInstitution(1)->pluck('id')->toArray();
        $inst2Ids = SecureAttachment::forInstitution(2)->pluck('id')->toArray();

        expect($inst1Ids)->toContain($att1->id)
            ->and($inst1Ids)->not->toContain($att2->id)
            ->and($inst2Ids)->toContain($att2->id)
            ->and($inst2Ids)->not->toContain($att1->id);
    });

    it('duplicate detection does not leak SHA-256 existence across institutions', function (): void {
        Storage::fake('attachments');
        config(['attachments.disk' => 'attachments']);

        $svc = new SecureAttachmentService;
        $file = fakePdf('same.pdf');

        $svc->store($file, dlUploaderContext(institutionId: 1));
        $svc->store($file, dlUploaderContext(institutionId: 2));

        // Two separate rows — no cross-institution deduplication
        expect(SecureAttachment::count())->toBe(2);
    });

    it('upload is attributed to the correct institution', function (): void {
        Storage::fake('attachments');
        config(['attachments.disk' => 'attachments']);

        $svc = new SecureAttachmentService;
        $file = fakePdf('doc.pdf');

        $attachment = $svc->store($file, dlUploaderContext(institutionId: 7));

        expect($attachment->institution_id)->toBe(7);
    });

});

// ---------------------------------------------------------------------------
// Audit events
// ---------------------------------------------------------------------------

describe('Audit events', function (): void {

    it('emits attachment.uploaded audit event after a successful upload', function (): void {
        Storage::fake('attachments');
        config(['attachments.disk' => 'attachments']);

        $svc = new SecureAttachmentService;
        $file = fakePdf();

        $svc->store($file, dlUploaderContext(institutionId: 1));

        $event = DB::table('audit_events')
            ->where('action', 'attachment.uploaded')
            ->where('source_module', 'Attachments')
            ->first();

        expect($event)->not->toBeNull()
            ->and($event->actor_type)->toBe('administrative')
            ->and($event->institution_id)->toBe(1);
    });

    it('emits attachment.downloaded audit event on a successful admin download', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create();
        $attachment = createAvailableAttachment(institutionId: 1);

        grantAdminPermission($admin->id, 'attachment.read');

        $this->actingAs($admin, 'admin');
        $this->get(route('admin.attachments.download', $attachment->id));

        $event = DB::table('audit_events')
            ->where('action', 'attachment.downloaded')
            ->where('source_module', 'Attachments')
            ->where('actor_type', 'administrative')
            ->first();

        expect($event)->not->toBeNull()
            ->and($event->institution_id)->toBe(1);
    });

});
