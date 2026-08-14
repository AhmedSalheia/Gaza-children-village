<?php

declare(strict_types=1);

namespace Modules\People\Services;

use SensitiveParameter;

/**
 * Normalizes Palestinian National ID strings.
 *
 * Rules (from ADR F12):
 *  1. Convert Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩) to ASCII digits (0–9).
 *  2. Remove spaces and hyphens.
 *  3. Require exactly 9 numeric ASCII digits.
 *  4. Do not implement checksum validation.
 *
 * Raw identifier values must never appear in log messages or exceptions.
 */
final class PalestinianIdNormalizer
{
    /** Arabic-Indic digit → ASCII digit mapping. */
    private const ARABIC_TO_ASCII = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Normalize and return the 9-digit string, or throw on invalid input.
     *
     * @throws \InvalidArgumentException if the value cannot be normalized to 9 digits.
     */
    public function normalize(#[SensitiveParameter] string $raw): string
    {
        // Step 1: convert Arabic-Indic digits to ASCII
        $value = strtr($raw, self::ARABIC_TO_ASCII);

        // Step 2: remove spaces and hyphens
        $value = str_replace([' ', '-'], '', $value);

        // Step 3: validate — must be exactly 9 ASCII digits
        if (! preg_match('/^\d{9}$/', $value)) {
            // Do not include $raw or $value in the message (privacy rule).
            throw new \InvalidArgumentException(
                'Palestinian national ID must normalize to exactly 9 numeric digits.'
            );
        }

        return $value;
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
     * Return the masked display form: last 4 digits visible, rest replaced with X.
     *
     * Input must already be normalized.
     */
    public function mask(string $normalized): string
    {
        return str_repeat('X', 5).substr($normalized, -4);
    }
}
