<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

/**
 * Input for SetInstitutionFeatureOverride.
 *
 * reason is temporarily nullable for F06. Management UI must not expose
 * override mutation until actor tracking, permission checks, and Audit
 * module integration exist (planned for post-F17). At that point reason
 * should be made required and every mutation audited with actor reference
 * and timestamp.
 */
final readonly class SetInstitutionFeatureOverrideData
{
    public function __construct(
        public bool $isEnabled,
        public ?string $reason = null,
    ) {}
}
