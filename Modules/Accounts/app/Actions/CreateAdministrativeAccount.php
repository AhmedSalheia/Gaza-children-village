<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Modules\Accounts\Data\CreateAdministrativeAccountData;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Models\AdministrativeAccount;

final class CreateAdministrativeAccount
{
    public function __invoke(CreateAdministrativeAccountData $data): AdministrativeAccount
    {
        return AdministrativeAccount::create([
            'username' => $data->username,
            'password' => $data->password,
            'status' => AccountStatus::Pending->value,
        ]);
    }
}
