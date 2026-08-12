<?php

declare(strict_types=1);

use Modules\Authorization\Actions\ResolveOperationalContext;
use Modules\Authorization\Contracts\OperationalScopeAuthorizer;
use Modules\Authorization\Data\ActorCategory;
use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\ActorSource;
use Modules\Authorization\Data\AuthorizedOperationalScope;
use Modules\Authorization\Data\OperationalContext;
use Modules\Authorization\Data\Portal;
use Modules\Authorization\Data\ResolvedOperationalScope;
use Modules\Authorization\Data\ScopeRequirement;
use Modules\Authorization\Data\UntrustedOperationalScope;

function scopeAuthorizer(array $knownChains): OperationalScopeAuthorizer
{
    return new class($knownChains) implements OperationalScopeAuthorizer
    {
        public function __construct(private readonly array $knownChains) {}

        public function resolveScope(
            Portal $portal,
            ActorReference $actor,
            UntrustedOperationalScope $scope,
        ): ResolvedOperationalScope {
            $requested = [
                $scope->institutionReference,
                $scope->institutionSemesterReference,
                $scope->operationalPeriodReference,
            ];

            if (! in_array($requested, $this->knownChains, true)) {
                throw new RuntimeException('The scope chain is unknown or does not belong together.');
            }

            return new ResolvedOperationalScope(...$requested);
        }
    };
}

function adminActor(ActorSource $source = ActorSource::Request): ActorReference
{
    return new ActorReference(
        Portal::Admin,
        ActorCategory::AdminAccount,
        $source,
        'account:admin-test',
    );
}

it('constructs a trusted context only after explicit scope resolution', function (): void {
    $candidate = new UntrustedOperationalScope('institution:a', 'semester:a-2026-1', 'period:a-1');
    $resolver = new ResolveOperationalContext(scopeAuthorizer([
        ['institution:a', 'semester:a-2026-1', 'period:a-1'],
    ]));

    $context = $resolver->execute(
        Portal::Admin,
        adminActor(),
        $candidate,
        ScopeRequirement::OperationalPeriod,
    );

    expect($context)
        ->toBeInstanceOf(OperationalContext::class)
        ->and($context->portal)->toBe(Portal::Admin)
        ->and($context->scope->institutionReference)->toBe('institution:a')
        ->and($context->scope->institutionSemesterReference)->toBe('semester:a-2026-1')
        ->and($context->scope->operationalPeriodReference)->toBe('period:a-1');
});

it('does not accept untrusted scope input as a trusted context scope', function (): void {
    $authorizedConstructor = new ReflectionMethod(AuthorizedOperationalScope::class, '__construct');
    $constructor = new ReflectionMethod(OperationalContext::class, '__construct');
    $scopeParameter = $constructor->getParameters()[2];

    expect($authorizedConstructor->isPrivate())->toBeTrue()
        ->and($scopeParameter->getType()?->getName())
        ->toBe(AuthorizedOperationalScope::class)
        ->not->toBe(UntrustedOperationalScope::class);
});

it('rejects portal and account category mismatches', function (): void {
    expect(fn () => new ActorReference(
        Portal::Staff,
        ActorCategory::AdminAccount,
        ActorSource::Request,
        'account:admin-test',
    ))->toThrow(InvalidArgumentException::class);

    $scope = AuthorizedOperationalScope::resolve(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope(null, null, null),
        scopeAuthorizer([[null, null, null]]),
    );

    expect(fn () => new OperationalContext(Portal::Staff, adminActor(), $scope))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an institution semester without its institution', function (): void {
    $resolver = new ResolveOperationalContext(scopeAuthorizer([]));

    expect(fn () => $resolver->execute(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope(null, 'semester:a-2026-1', null),
        ScopeRequirement::InstitutionSemester,
    ))->toThrow(InvalidArgumentException::class);

});

it('rejects a period without its institution semester', function (): void {
    $resolver = new ResolveOperationalContext(scopeAuthorizer([]));

    expect(fn () => $resolver->execute(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope('institution:a', null, 'period:a-1'),
        ScopeRequirement::OperationalPeriod,
    ))->toThrow(InvalidArgumentException::class);

});

it('fails closed for mismatched and unknown scope chains', function (array $candidate): void {
    $resolver = new ResolveOperationalContext(scopeAuthorizer([
        ['institution:a', 'semester:a-2026-1', 'period:a-1'],
    ]));

    expect(fn () => $resolver->execute(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope(...$candidate),
        ScopeRequirement::OperationalPeriod,
    ))->toThrow(RuntimeException::class, 'unknown or does not belong together');
})->with([
    'mismatched institution' => [['institution:b', 'semester:a-2026-1', 'period:a-1']],
    'mismatched institution semester' => [['institution:a', 'semester:b-2026-1', 'period:a-1']],
    'unknown operational period' => [['institution:a', 'semester:a-2026-1', 'period:unknown']],
]);

it('fails closed when a required scope is explicitly absent', function (): void {
    $resolver = new ResolveOperationalContext(scopeAuthorizer([
        [null, null, null],
        ['institution:a', null, null],
    ]));

    expect(fn () => $resolver->execute(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope(null, null, null),
        ScopeRequirement::Institution,
    ))->toThrow(RuntimeException::class, 'required institution scope');

    expect(fn () => $resolver->execute(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope('institution:a', null, null),
        ScopeRequirement::InstitutionSemester,
    ))->toThrow(RuntimeException::class, 'required institution_semester scope');
});

it('does not accept an authorized result that differs from the requested chain', function (): void {
    $authorizer = new class implements OperationalScopeAuthorizer
    {
        public function resolveScope(
            Portal $portal,
            ActorReference $actor,
            UntrustedOperationalScope $scope,
        ): ResolvedOperationalScope {
            return new ResolvedOperationalScope('institution:b', null, null);
        }
    };

    $resolver = new ResolveOperationalContext($authorizer);

    expect(fn () => $resolver->execute(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope('institution:a', null, null),
        ScopeRequirement::Institution,
    ))->toThrow(RuntimeException::class, 'does not match');
});

it('keeps trusted context values immutable', function (): void {
    $scope = AuthorizedOperationalScope::resolve(
        Portal::Admin,
        adminActor(),
        new UntrustedOperationalScope('institution:a', null, null),
        scopeAuthorizer([['institution:a', null, null]]),
    );
    $context = new OperationalContext(
        Portal::Admin,
        adminActor(),
        $scope,
    );

    expect(fn () => $context->portal = Portal::Staff)->toThrow(Error::class)
        ->and(fn () => $context->scope->institutionReference = 'institution:b')->toThrow(Error::class)
        ->and((new ReflectionClass($context))->isReadOnly())->toBeTrue()
        ->and((new ReflectionClass($context->actor))->isReadOnly())->toBeTrue()
        ->and((new ReflectionClass($context->scope))->isReadOnly())->toBeTrue();
});

it('requires explicit actor sources and explicit scope requirements for jobs and cli', function (
    ActorSource $source,
): void {
    $actor = adminActor($source);
    $resolver = new ResolveOperationalContext(scopeAuthorizer([
        ['institution:a', null, null],
    ]));

    $context = $resolver->execute(
        Portal::Admin,
        $actor,
        new UntrustedOperationalScope('institution:a', null, null),
        ScopeRequirement::Institution,
    );

    expect($context->actor->source)->toBe($source)
        ->and($context->scope->institutionReference)->toBe('institution:a');
})->with([
    'queued job' => ActorSource::QueuedJob,
    'CLI action' => ActorSource::Cli,
]);

it('keeps a system actor portal-bound and subject to scope authorization', function (): void {
    $actor = new ActorReference(
        Portal::Admin,
        ActorCategory::System,
        ActorSource::SystemProcess,
        'system:maintenance-test',
    );
    $resolver = new ResolveOperationalContext(scopeAuthorizer([]));

    expect(fn () => $resolver->execute(
        Portal::Admin,
        $actor,
        new UntrustedOperationalScope('institution:unknown', null, null),
        ScopeRequirement::Institution,
    ))->toThrow(RuntimeException::class, 'unknown or does not belong together');
});
