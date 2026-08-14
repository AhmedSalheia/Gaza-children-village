<?php

declare(strict_types=1);

namespace Modules\Accounts\Services;

final class LoginIdentifierNormalizer
{
    /**
     * Normalize a login identifier to its canonical form.
     *
     * All identifiers (username and login_identifier) are stored in lowercase
     * with leading/trailing whitespace removed. This ensures consistent lookup
     * regardless of the case the user typed at the keyboard.
     */
    public function normalize(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }
}
