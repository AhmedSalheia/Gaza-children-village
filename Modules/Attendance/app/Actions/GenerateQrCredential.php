<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffQrCredential;

/**
 * Generate a new QR credential for a staff member.
 *
 * SECURITY CONTRACT
 * -----------------
 * • A 32-byte cryptographically random token is generated.
 * • Only the HMAC-SHA256 hash is stored in the database.
 * • The plaintext token is returned ONCE in the result and must be shown
 *   to the staff member immediately; it cannot be retrieved again.
 * • Any existing active credential for the same staff member is revoked
 *   atomically before the new one is created.
 *
 * Returns array{credential: StaffQrCredential, plaintext_token: string}
 *
 * @return array{credential: StaffQrCredential, plaintext_token: string}
 */
final class GenerateQrCredential
{
    public function __invoke(
        int $staffProfileId,
        int $issuedByStaffProfileId,
    ): array {
        // Verify staff profile exists
        $exists = DB::table('staff_profiles')->where('id', $staffProfileId)->exists();

        if (! $exists) {
            throw new StaffAttendanceException(
                "Staff profile #{$staffProfileId} not found."
            );
        }

        return DB::transaction(function () use ($staffProfileId, $issuedByStaffProfileId): array {
            // Revoke any existing active credentials for this staff member
            DB::table('staff_qr_credentials')
                ->where('staff_profile_id', $staffProfileId)
                ->where('is_active', true)
                ->update([
                    'is_active'                    => false,
                    'revoked_at'                   => now(),
                    'revoked_by_staff_profile_id'  => $issuedByStaffProfileId,
                ]);

            // Generate plaintext token (32 random bytes → base64url, 43 chars)
            $plaintextToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

            // Compute HMAC — deterministic, O(1) lookup on scan
            $tokenHash = hash_hmac('sha256', $plaintextToken, config('app.key'));

            $credential = new StaffQrCredential();
            $credential->staff_profile_id            = $staffProfileId;
            $credential->token_hash                  = $tokenHash;
            $credential->is_active                   = true;
            $credential->issued_at                   = now();
            $credential->issued_by_staff_profile_id  = $issuedByStaffProfileId;
            $credential->save();

            return [
                'credential'      => $credential,
                'plaintext_token' => $plaintextToken,
            ];
        });
    }
}
