<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;
use Modules\Authorization\Data\ActorCategory;
use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\ActorSource;
use Modules\Authorization\Data\Portal;
use RuntimeException;

/**
 * Converts an authenticated account model into the F02 ActorReference value object.
 *
 * The opaque reference field is the account's primary key cast to string.
 * This mapper depends only on public Authorization Data surfaces, which is
 * permitted by the module dependency graph (Accounts → Authorization).
 */
final class AccountActorMapper
{
    public function toActorReference(
        AdministrativeAccount|StaffAccount|GuardianAccount $account,
        ActorSource $source,
    ): ActorReference {
        [$portal, $category] = match (true) {
            $account instanceof AdministrativeAccount => [Portal::Admin, ActorCategory::AdminAccount],
            $account instanceof StaffAccount => [Portal::Staff, ActorCategory::StaffAccount],
            $account instanceof GuardianAccount => [Portal::Guardian, ActorCategory::GuardianAccount],
            default => throw new RuntimeException('Unknown account type: '.get_class($account)),
        };

        return new ActorReference(
            portal: $portal,
            category: $category,
            source: $source,
            reference: (string) $account->getKey(),
        );
    }
}
