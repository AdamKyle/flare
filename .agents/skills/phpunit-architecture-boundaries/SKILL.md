---
name: phpunit-architecture-boundaries
description: Use when adding or reviewing Pest/PHPUnit tests that enforce app/Game module dependency direction, public contracts, internal namespaces, or data ownership.
---

# PHPUnit Architecture Boundaries

## Incremental Enforcement

Architecture tests prevent a removed dependency from returning. They do not declare the entire legacy repository compliant in one change.

Before adding a rule:

- map the current imports in the exact namespaces being protected;
- prove the forbidden dependency has been removed from the scoped path;
- choose the narrowest namespace and direction that expresses the real ownership decision;
- confirm legitimate framework, model, test, and compatibility dependencies are not accidentally banned.

Do not add a broad rule that immediately requires an unrelated repository-wide refactor.

## Required Rules for a New Boundary

Where supported by the installed Pest version, add focused `arch()` tests proving the applicable invariants:

- internal implementation namespaces are used only by their owning module;
- public contract namespaces do not depend on internal implementation namespaces;
- a prohibited reverse dependency cannot return;
- consumers depend on the providing contract rather than its concrete service;
- a protected contract does not expose Eloquent models or query builders.

Architecture tests cannot detect raw SQL, dynamic class names, model relationship traversal, cache keys, or runtime service location. Inspect and behavior-test those paths separately.

## Contract Behavior Tests

Test public module behavior through the contract resolved by Laravel. Assert stable typed results or snapshots and the established non-throwing failure behavior. Consumer tests may fake the public contract; internal implementation tests should continue using real collaborators unless a repository mocking rule permits otherwise.

## Repository Test Rules

All existing fixture, database-isolation, mocking, attribute, spacing, and one-behavior rules remain mandatory. Do not introduce database transactions or transaction-oriented tests as part of architecture enforcement. Run only the focused architecture and directly affected behavior tests unless the user authorizes broader execution.
