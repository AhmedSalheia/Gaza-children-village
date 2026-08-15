<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Attendance\Actions\SubmitScanEvent;
use Modules\Attendance\Exceptions\StaffAttendanceException;

/**
 * Public QR attendance scan submission endpoint.
 *
 * Rate-limited at 60 attempts per minute per IP address.
 * CSRF-exempt — the HMAC token acts as the authentication secret, and
 * external QR scanning devices cannot hold a session CSRF token.
 *
 * Responds with JSON for API/QR-device clients (when Accept: application/json
 * or Content-Type: application/json is set), and redirects with session flash
 * for browser-based HTML form submissions (the manual fallback form).
 */
final class ScanController extends Controller
{
    public function store(Request $request, SubmitScanEvent $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token'               => ['required', 'string', 'min:20', 'max:128'],
            'operational_period'  => ['required', 'integer', 'min:1'],
            'direction'           => ['sometimes', 'string', 'in:arrival,departure,unknown'],
            'device_fingerprint'  => ['sometimes', 'nullable', 'string', 'max:128'],
        ]);

        try {
            $result = $action(
                plaintextToken:      $validated['token'],
                operationalPeriodId: (int) $validated['operational_period'],
                direction:           $validated['direction'] ?? 'unknown',
                deviceFingerprint:   $validated['device_fingerprint'] ?? null,
            );
        } catch (StaffAttendanceException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->with('scan_error', $e->getMessage())
                ->withInput();
        }

        $successMessage = $result['is_duplicate']
            ? 'Scan already recorded. Awaiting secretary review.'
            : 'Scan recorded successfully. Awaiting secretary review.';

        if ($request->expectsJson()) {
            return response()->json([
                'status'       => 'ok',
                'event_id'     => $result['event']->id,
                'is_duplicate' => $result['is_duplicate'],
                'message'      => $successMessage,
            ], 201);
        }

        return back()->with('scan_success', $successMessage);
    }
}
