<?php

declare(strict_types=1);

namespace Modules\Attachments\Data;

/**
 * Immutable value object carrying the uploader's authenticated identity.
 *
 * Always derived from the portal session in controller/Livewire layer.
 * Never accepts actor identity from form fields or query parameters.
 */
final class UploaderContext
{
    public function __construct(
        /** 'administrative' | 'staff' */
        public readonly string $actorType,
        /** Primary key of the AdministrativeAccount or StaffAccount */
        public readonly int $accountId,
        /** 'admin' | 'staff' */
        public readonly string $portal,
        /** Institution the uploader is acting within */
        public readonly int $institutionId,
    ) {}
}
