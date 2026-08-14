<?php

declare(strict_types=1);

namespace Modules\Accounts\Services;

use SensitiveParameter;

/**
 * Centralized password policy.
 *
 * Reads policy constants from config/account-challenges.php so that the
 * constraints can be changed in one place and are testable without
 * modifying class code.
 *
 * Privacy: the password parameter carries the SensitiveParameter attribute
 * so that the value is scrubbed from stack traces and error reports.
 * NEVER log or include password input in validation messages.
 */
final class PasswordPolicy
{
    public function minLength(): int
    {
        return (int) config('account-challenges.password.min_length', 10);
    }

    public function maxLength(): int
    {
        return (int) config('account-challenges.password.max_length', 128);
    }

    /**
     * Returns true when the password satisfies every policy constraint.
     */
    public function passes(#[SensitiveParameter] string $password): bool
    {
        $length = strlen($password);

        if ($length < $this->minLength() || $length > $this->maxLength()) {
            return false;
        }

        // Must contain at least one ASCII letter
        if (! preg_match('/[a-zA-Z]/', $password)) {
            return false;
        }

        // Must contain at least one ASCII digit
        if (! preg_match('/[0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Return a set of Laravel validation rule strings for use in FormRequests.
     *
     * Note: `confirmed` expects a matching `<field>_confirmation` input.
     * Do NOT log validation input that fails these rules.
     *
     * @return list<string>
     */
    public function laravelRules(): array
    {
        return [
            'required',
            'string',
            'min:'.$this->minLength(),
            'max:'.$this->maxLength(),
            'regex:/[a-zA-Z]/',
            'regex:/[0-9]/',
            'confirmed',
        ];
    }
}
