# Operational context contracts

## Purpose and ownership

F02 establishes a small, fail-closed vocabulary for carrying an actor and an explicitly authorized operational scope. All contracts and implementations belong to the `Authorization` module because they either describe authorization input, represent its result, or manage the result for one Laravel lifecycle. No root `App` class is needed.

The context is not authentication, a permission decision, or evidence that any particular action is allowed. Future policies and actions must still authorize their own operation. It is only a trusted statement that the actor, portal, and selected scope chain were resolved together by the configured authorizer.

## Public contracts and data

The public surface is deliberately limited to namespaces permitted by `docs/MODULE_CONVENTIONS.md`:

- `Data\Portal` names the `admin`, `staff`, and `guardian` portals.
- `Data\ActorCategory` distinguishes admin, staff, guardian, and explicit system actors. System actors receive no inherent permission or bypass.
- `Data\ActorSource` records whether the actor came from a request, queued job, CLI action, or system process.
- `Data\ActorReference` combines portal, actor category, source, and an opaque reference. Account categories must match their portal.
- `Data\UntrustedOperationalScope` carries external institution, institution-semester, and optional operational-period candidates. `null` explicitly means that a level is absent; blank strings and sentinel values are not absence.
- `Contracts\OperationalScopeAuthorizer` is the adapter boundary that must resolve the candidates, validate their parent relationships, and authorize them for the actor and portal.
- `Data\ResolvedOperationalScope` is the authorizer adapter's returned candidate. It is not itself trusted context.
- `Data\AuthorizedOperationalScope` validates that the adapter returned exactly the requested chain, enforces the required parent shape, and has a private constructor so raw values cannot mint it directly.
- `Data\ScopeRequirement` makes the scope required by a call site explicit: none, institution, institution semester, or operational period. `None` means an explicitly declared scope-free operation; it is not an authorization bypass and still requires an authorizer.
- `Actions\ResolveOperationalContext` checks the candidate shape, calls the authorizer, checks the returned chain and required scope, and only then creates `Data\OperationalContext`.
- `Contracts\OperationalContextStore` exposes the current context for one application lifecycle. Its internal implementation is bound with Laravel's `scoped` lifetime and starts empty.

Opaque references are transport values only. F02 assigns no UUID, ULID, integer, composite-key, or other database identity semantics to them. Future adapters translate between these opaque references and the key strategy approved for their owning module.

## Trusted versus untrusted

Route parameters, query strings, request bodies, cookies, session data, job payloads, and CLI arguments are untrusted. They may populate `UntrustedOperationalScope`, but they cannot be placed in `OperationalContext`.

The trust flow is:

1. The entry point creates an explicit `ActorReference`, including portal, category, source, and opaque account/system reference.
2. It creates `UntrustedOperationalScope`, using `null` for each absent level, and declares a `ScopeRequirement`.
3. `ResolveOperationalContext` rejects malformed parent shape before resolution.
4. The configured `OperationalScopeAuthorizer` resolves every supplied reference, validates institution → institution semester → operational period ownership, and applies the future actor authorization rules.
5. Authorization checks that the returned scope is the same chain requested and that the declared requirement is satisfied.
6. Only then is an immutable `OperationalContext` returned and, when appropriate, stored for the current lifecycle.

Unknown references, mismatched chains, missing required scope, a missing authorizer binding, or an empty lifecycle store all fail closed. The Authorization service provider intentionally supplies no default `OperationalScopeAuthorizer` and contains no allow-all or super-admin path.

## Requests, jobs, and CLI lifecycles

The scoped store is empty until trusted context is explicitly set, accepts only one context per lifecycle, and is recreated when Laravel flushes scoped instances. Request middleware may seed it in a later PR after authentication and database adapters exist; F02 adds no middleware or endpoint.

Queued jobs and CLI actions must carry enough serialized input to reconstruct an explicit actor reference, actor source, scope candidate, and scope requirement. A worker or command must invoke `ResolveOperationalContext` for its own lifecycle. It must not inherit context from the dispatching request or treat a system actor as implicitly authorized. Long-running processes must use Laravel's scoped-lifecycle flushing rather than retaining the store between work items.

## Future database adapters

No repository or production adapter exists in F02. The expected dependency direction is:

- F03 (`Organization`) can expose its institution lookup through its own public surface and may provide an institution-only implementation of the Authorization contract when that release needs one. `Authorization` must not depend back on `Organization`.
- F07 (`AcademicCalendar`) will own academic-year and semester catalogue data used by later relationship checks. These catalogue identifiers are not added to F02 context because the operational scope is the institution-semester established in F08.
- F08 (`AcademicCalendar`) can implement the complete `OperationalScopeAuthorizer`, depending on the public `Organization` and `Authorization` contracts as already permitted. It must resolve the institution, prove that the institution semester belongs to that institution, prove that an optional period belongs to that institution semester, and then apply the actor's future authorization rules.

Adapter placement and composition must follow the approved dependency graph. Implementations may be split or decorated if later requirements need it, but callers continue to depend only on `OperationalScopeAuthorizer`; they must not reach into repositories, Eloquent models, HTTP classes, or UI components in another module.

## Decisions intentionally left open

F02 does not decide:

- database primary-key formats or how opaque references are encoded;
- account-table topology, guard/provider configuration, login identifiers, or authenticated-account adapters (F09 and later);
- the permission catalogue, central-management read scope, institutional mutation abilities, or system-actor capabilities (F17 and workflow PRs);
- which concrete actions require institution, institution-semester, period, or explicitly scope-free context;
- portal scope-selection UI, persistence in session/cookies, or middleware ordering;
- the final production adapter composition across F03, F07, and F08.

Those decisions must be made in their owning PRs. Nothing in F02 grants access in advance.
