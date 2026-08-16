<?php

declare(strict_types=1);

namespace Modules\Workflow\Contracts;

/**
 * Portal-bound reconfirmation challenge.
 *
 * Concrete implementations live in the portal layer (e.g. Staff or Admin modules)
 * and derive actor identity from the currently authenticated session rather than
 * from caller-supplied parameters.
 *
 * The Workflow module defines this interface; the portal layer provides the
 * implementation at runtime via the service container.
 *
 * Callers pass the raw credential from the submitted form; checkCredential()
 * verifies it against the appropriate guard and credential store server-side.
 * The Workflow module never trusts a pre-computed boolean from the caller.
 */
interface ReconfirmationChallengeContract
{
    /**
     * The actor type that identifies the kind of principal performing the reconfirmation
     * (e.g. 'administrative', 'staff'). Derived from the authenticated session.
     */
    public function actorType(): string;

    /**
     * The account ID of the currently authenticated user.
     * Derived from the authenticated session — never accepted from the client.
     */
    public function actorAccountId(): int;

    /**
     * The portal this reconfirmation belongs to (e.g. 'admin', 'staff').
     * Derived from the authenticated session.
     */
    public function portal(): string;

    /**
     * Verify the submitted credential (password) against the authentication backend.
     *
     * Implementations MUST NOT cache the result across calls. Each call performs a
     * fresh credential check against the guard's user provider and password hasher.
     *
     * @return bool true on correct credential; false on incorrect credential.
     *              The service will record a failed attempt and throw on false.
     */
    public function checkCredential(string $credential): bool;
}
