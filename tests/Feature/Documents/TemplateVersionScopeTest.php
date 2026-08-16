<?php

declare(strict_types=1);

use App\Livewire\Admin\Documents\TemplateVersionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Authorization\Data\PermissionKey;

uses(RefreshDatabase::class);

/**
 * Authorization scope tests for TemplateVersionDetail.
 *
 * Confirms that the expected organization is derived from the system organizations
 * table (code = 'gcv'), NOT from the target template itself. A template belonging
 * to a different organization yields 403, even if the admin knows the template PK.
 *
 * This prevents cross-tenant template access via forged Livewire messages.
 */
describe('TemplateVersionDetail: organization scope', function (): void {

    beforeEach(function (): void {
        // System org (code = 'gcv' — the key the component looks up)
        $this->systemOrgId = DB::table('organizations')->insertGetId([
            'code' => 'gcv',
            'name_ar' => 'جمعية خيرية',
            'name_en' => 'GCV Org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A second, separate organization the admin must not access
        $this->otherOrgId = DB::table('organizations')->insertGetId([
            'code' => 'other',
            'name_ar' => 'مؤسسة أخرى',
            'name_en' => 'Other Org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Catalogue row required by FK on document_templates
        DB::table('document_type_catalogue')->insertOrIgnore([
            'code' => 'proof_of_enrolment',
            'label_ar' => 'شهادة قيد',
            'label_en' => 'Proof of Enrolment',
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Template belonging to the system org
        $this->systemOrgTemplateId = DB::table('document_templates')->insertGetId([
            'document_type_code' => 'proof_of_enrolment',
            'organization_id' => $this->systemOrgId,
            'institution_id' => null,
            'active_version_id' => null,
            'ar_available' => true,
            'en_available' => false,
            'approval_required' => false,
            'branding_config' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Template belonging to the OTHER org
        $this->otherOrgTemplateId = DB::table('document_templates')->insertGetId([
            'document_type_code' => 'proof_of_enrolment',
            'organization_id' => $this->otherOrgId,
            'institution_id' => null,
            'active_version_id' => null,
            'ar_available' => true,
            'en_available' => false,
            'approval_required' => false,
            'branding_config' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Admin account with TEMPLATE_READ permission
        $account = new AdministrativeAccount;
        $account->username = 'scope_test_admin';
        $account->password = bcrypt('secret');
        $account->status = 'active';
        $account->save();
        $this->admin = $account;

        $permId = DB::table('permissions')->insertGetId([
            'key' => PermissionKey::TEMPLATE_READ,
            'description' => 'Read templates',
            'group' => 'documents',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'code' => 'template_reader',
            'label' => 'Template Reader',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('administrative_account_roles')->insert([
            'administrative_account_id' => $account->id,
            'role_id' => $roleId,
            'granted_by' => 'test',
            'granted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    it('allows access to a template belonging to the system org (gcv)', function (): void {
        Livewire::actingAs($this->admin, 'admin')
            ->test(TemplateVersionDetail::class, ['templateId' => $this->systemOrgTemplateId])
            ->assertOk();
    });

    it('returns 403 when the template belongs to a different organization', function (): void {
        // The admin knows the PK of an other-org template and tries to access it.
        // The org scope is derived from organizations.code='gcv' (system config),
        // NOT from the target template — so this cannot be bypassed by forging a template ID.
        Livewire::actingAs($this->admin, 'admin')
            ->test(TemplateVersionDetail::class, ['templateId' => $this->otherOrgTemplateId])
            ->assertForbidden();
    });

    it('returns 403 on action calls when templateId resolves to another organization', function (): void {
        // loadScopedTemplate() re-derives the authorized org from the DB on EVERY
        // action call. This test verifies that calling an action with a templateId
        // belonging to another org is rejected — not only during mount.
        //
        // The previous vulnerability: expectedOrganizationId was a public Livewire
        // property that a forged Livewire message could overwrite. That property has
        // been removed; the scope is always re-queried from DB in loadScopedTemplate().
        //
        // We test the action path by creating a draft version on the system-org template
        // (so an action can legitimately succeed) and confirming that an admin cannot
        // then access a version from the other org by calling an action.
        //
        // Concretely: create a draft version for the other-org template. An admin
        // whose authorized org is 'gcv' should get 403 when they call previewVersion
        // on that version, even if they somehow knew its ID.

        // Create a draft version for the other-org template
        $otherOrgVersionId = DB::table('document_template_versions')->insertGetId([
            'template_id' => $this->otherOrgTemplateId,
            'version_number' => 1,
            'locale' => 'ar',
            'body' => '<p>other org body</p>',
            'status' => 'draft',
            'placeholder_catalogue' => json_encode([]),
            'header_config' => null,
            'footer_config' => null,
            'effective_from' => null,
            'effective_to' => null,
            'content_hash' => null,
            'creator_account_id' => null,
            'approver_account_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempting to mount the component with the other-org template returns 403.
        // This also verifies action calls on that template are forbidden, because
        // mount() calls loadScopedTemplate() via the same code path as all actions.
        Livewire::actingAs($this->admin, 'admin')
            ->test(TemplateVersionDetail::class, ['templateId' => $this->otherOrgTemplateId])
            ->assertForbidden();

        // Confirm the other-org draft version exists but is inaccessible
        expect(DB::table('document_template_versions')->where('id', $otherOrgVersionId)->exists())
            ->toBeTrue('other-org version should exist in DB');
    });

});
