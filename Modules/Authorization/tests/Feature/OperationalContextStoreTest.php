<?php

declare(strict_types=1);

use Modules\Authorization\Actions\ResolveOperationalContext;
use Modules\Authorization\Contracts\OperationalContextStore;
use Modules\Authorization\Contracts\OperationalScopeAuthorizer;
use Modules\Authorization\Data\ActorCategory;
use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\ActorSource;
use Modules\Authorization\Data\Portal;
use Modules\Authorization\Data\ResolvedOperationalScope;
use Modules\Authorization\Data\ScopeRequirement;
use Modules\Authorization\Data\UntrustedOperationalScope;

it('does not register a default allow-all scope authorizer', function (): void {
    expect(app()->bound(OperationalScopeAuthorizer::class))->toBeFalse();
});

it('does not leak operational context between scoped lifecycles', function (): void {
    $firstLifecycle = app(OperationalContextStore::class);
    $actor = new ActorReference(
        Portal::Staff,
        ActorCategory::StaffAccount,
        ActorSource::Request,
        'account:staff-test',
    );
    $authorizer = new class implements OperationalScopeAuthorizer
    {
        public function resolveScope(
            Portal $portal,
            ActorReference $actor,
            UntrustedOperationalScope $scope,
        ): ResolvedOperationalScope {
            return new ResolvedOperationalScope(
                $scope->institutionReference,
                $scope->institutionSemesterReference,
                $scope->operationalPeriodReference,
            );
        }
    };
    $context = (new ResolveOperationalContext($authorizer))->execute(
        Portal::Staff,
        $actor,
        new UntrustedOperationalScope('institution:a', 'semester:a-2026-1', null),
        ScopeRequirement::InstitutionSemester,
    );

    $firstLifecycle->set($context);

    expect($firstLifecycle->has())->toBeTrue()
        ->and($firstLifecycle->current())->toBe($context)
        ->and(fn () => $firstLifecycle->set($context))->toThrow(LogicException::class);

    app()->forgetScopedInstances();

    $secondLifecycle = app(OperationalContextStore::class);

    expect($secondLifecycle)->not->toBe($firstLifecycle)
        ->and($secondLifecycle->has())->toBeFalse()
        ->and(fn () => $secondLifecycle->current())->toThrow(RuntimeException::class);
});
