<?php

declare(strict_types=1);

namespace Modules\Documents\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds an organization-scoped DocumentTemplate family record for every type
 * registered in the document_type_catalogue table.
 *
 * These are "shell" records that make the module operable immediately after a
 * fresh install. No template *version* is created here — administrators author
 * and activate versions through the admin portal (Admin → Documents → Templates).
 *
 * Organization scoping:
 *   organization_id is set to the ID of the GCV organization record (code='gcv'),
 *   not null. Using null would create an ambiguous "global" template visible and
 *   mutable by any admin on any organization in a multi-tenant deployment.
 *   Organization-wide means "applies to all institutions within that organization"
 *   — institution_id remains null for these seed records.
 *
 * Idempotent: uses updateOrInsert keyed on (document_type_code, organization_id,
 * institution_id) so it may be run multiple times without creating duplicates.
 *
 * Dependency: must run after DocumentTypeSeeder and OrganizationReferenceSeeder.
 */
final class DocumentTemplateFamilySeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = DB::table('organizations')->where('code', 'gcv')->value('id');

        if ($organizationId === null) {
            $this->command?->warn(
                'DocumentTemplateFamilySeeder: GCV organization not found — run OrganizationReferenceSeeder first.'
            );

            return;
        }

        $now = now()->toDateTimeString();
        $codes = DB::table('document_type_catalogue')
            ->orderBy('display_order')
            ->pluck('code');

        foreach ($codes as $code) {
            DB::table('document_templates')->updateOrInsert(
                [
                    'document_type_code' => $code,
                    'organization_id' => $organizationId,
                    'institution_id' => null,
                ],
                [
                    'active_version_id' => null,
                    'ar_available' => true,
                    'en_available' => false,
                    'approval_required' => false,
                    'branding_config' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
