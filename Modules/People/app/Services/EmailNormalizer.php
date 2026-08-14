<?php

declare(strict_types=1);

namespace Modules\People\Services;

use SensitiveParameter;

/**
 * Normalizes email addresses for storage and lookup.
 *
 * Rules:
 *  - Trim surrounding whitespace.
 *  - Normalize the domain part to lowercase.
 *  - Preserve the local part as-is (case sensitivity is spec-dependent per domain).
 *  - Validate that the result is a structurally valid email.
 *  - Do not perform DNS/MX validation.
 *
 * Raw values must never appear in error messages or logs.
 */
final class EmailNormalizer
{
    /**
     * Normalize an email address.
     *
     * @throws \InvalidArgumentException if the email is structurally invalid.
     */
    public function normalize(#[SensitiveParameter] string $raw): string
    {
        $trimmed = trim($raw);
        $parts = explode('@', $trimmed, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException('Email address is not structurally valid.');
        }

        $normalized = $parts[0].'@'.strtolower($parts[1]);

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email address is not structurally valid.');
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
     * Shows the domain part and the last character of the local part; masks the rest.
     */
    public function mask(#[SensitiveParameter] string $normalized): string
    {
        [$local, $domain] = explode('@', $normalized, 2);
        $maskedLocal = str_repeat('X', max(1, strlen($local) - 1)).substr($local, -1);

        return $maskedLocal.'@'.$domain;
    }
}
