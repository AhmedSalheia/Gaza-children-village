<?php

declare(strict_types=1);

namespace Modules\Accounts\Data;

final readonly class CreateAdministrativeAccountData
{
    public function __construct(
        public string $username,
        public string $password,
    ) {}
}
