<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Modules\Accounts\Data\CreateGuardianAccountData;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Models\GuardianAccount;

final class CreateGuardianAccount
{
    public function __invoke(CreateGuardianAccountData $data): GuardianAccount
    {
        return GuardianAccount::create([
            'login_identifier' => $data->loginIdentifier,
            'password' => $data->password,
            'status' => AccountStatus::Pending->value,
        ]);
    }
}
