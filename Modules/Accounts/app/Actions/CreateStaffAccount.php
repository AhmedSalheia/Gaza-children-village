<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Modules\Accounts\Data\CreateStaffAccountData;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Models\StaffAccount;

final class CreateStaffAccount
{
    public function __invoke(CreateStaffAccountData $data): StaffAccount
    {
        return StaffAccount::create([
            'username' => $data->username,
            'password' => $data->password,
            'status' => AccountStatus::Pending->value,
        ]);
    }
}
