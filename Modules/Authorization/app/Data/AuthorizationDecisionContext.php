<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

/**
 * Value object carrying everything the PolicyKernel needs to evaluate a decision.
 *
 * All fields are plain scalars / enums so this remains free of cross-module
 * model imports (Authorization has zero allowed dependencies).
 */
final class AuthorizationDecisionContext
{
    /**
     * @param  string  $permissionKey  One of PermissionKey::* constants.
     * @param  int  $accountId  The account making the request.
     * @param  string  $accountType  'administrative'|'staff'|'guardian'.
     * @param  string  $accountStatus  'active'|'suspended'|'locked'|'revoked'|...
     * @param  int|null  $institutionId  If scoped to an institution.
     * @param  int|null  $institutionSemesterId  If scoped to a semester.
     * @param  string|null  $semesterStatus  Status value of the semester if known.
     * @param  list<string>  $roleCodesHeld  Role codes resolved for this actor.
     */
    public function __construct(
        public readonly string $permissionKey,
        public readonly int $accountId,
        public readonly string $accountType,
        public readonly string $accountStatus,
        public readonly ?int $institutionId = null,
        public readonly ?int $institutionSemesterId = null,
        public readonly ?string $semesterStatus = null,
        public readonly array $roleCodesHeld = [],
    ) {}
}
