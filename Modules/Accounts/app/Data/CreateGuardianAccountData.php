<?php

declare(strict_types=1);

namespace Modules\Accounts\Data;

final readonly class CreateGuardianAccountData
{
    public function __construct(
        public string $loginIdentifier,
        public string $password,
    ) {}
}
