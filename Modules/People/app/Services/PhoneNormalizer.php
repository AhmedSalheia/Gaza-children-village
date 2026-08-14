<?php

declare(strict_types=1);

namespace Modules\People\Services;

use SensitiveParameter;

/**
 * Normalizes phone numbers for storage and lookup.
 *
 * Rules:
 *  - Requires an explicit international prefix (e.g. +970, +972, +1).
 *  - Does not guess ambiguous local-country codes.
 *  - Accepts +972 numbers; political context does not make a number invalid.
 *  - Removes spaces, hyphens, and parentheses from the local part.
 *  - Stores and delivers in E.164-like format (+CountryCode LocalDigits).
 *
 * Raw values must never appear in error messages or logs.
 */
final class PhoneNormalizer
{
    /**
     * Normalize a phone number to E.164 format (e.g. +97059XXXXXXX).
     *
     * @throws \InvalidArgumentException if the number cannot be safely normalized.
     */
    public function normalize(#[SensitiveParameter] string $raw): string
    {
        $trimmed = trim($raw);

        // Must start with a + prefix
        if (! str_starts_with($trimmed, '+')) {
            throw new \InvalidArgumentException(
                'Phone number must include an explicit international prefix (e.g. +970).'
            );
        }

        // Remove all non-digit characters except the leading +
        $normalized = '+'.preg_replace('/[^\d]/', '', substr($trimmed, 1));

        // Basic length sanity: E.164 allows 8–15 digits after the +
        $digitCount = strlen($normalized) - 1;
        if ($digitCount < 7 || $digitCount > 15) {
            throw new \InvalidArgumentException(
                'Phone number digit count is outside the allowed range.'
            );
        }

        return $normalized;
    }

    /**
     * Return true if the value can be normalized without throwing.
     */
    public function isValid(#[SensitiveParameter] string $raw): bool
    {
        try {
            $this->normalize($raw);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Return a masked form suitable for display.
     * Shows last 4 digits; masks the rest with X.
     */
    public function mask(#[SensitiveParameter] string $normalized): string
    {
        $prefix = '+';
        $digits = substr($normalized, 1);
        $visible = min(4, strlen($digits));
        $masked = max(0, strlen($digits) - $visible);

        return $prefix.str_repeat('X', $masked).substr($digits, -$visible);
    }
}
