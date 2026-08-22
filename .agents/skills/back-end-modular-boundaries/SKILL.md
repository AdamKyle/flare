---
name: back-end-modular-boundaries
description: Use whenever Laravel backend work reads, calls, imports, mutates, broadcasts, or coordinates across two or more app/Game modules, or when reviewing/refactoring backend architecture.
---

# Back End Modular Boundaries

## Purpose

`app/Game/<Module>` represents a business capability. A directory alone is not a boundary. The dependency arrows, owned decisions, owned writes, and deliberately exposed operations define the boundary.

Apply this skill before changing code that crosses module namespaces. Also apply `back-end-module-contracts`, `back-end-data-ownership`, and, when events/jobs/listeners are involved, `back-end-events-and-module-coordination`.

## Inspect Before Designing

Trace the complete touched use case through controllers, requests, services, handlers, jobs, events, listeners, providers, models, relationships, routes, tests, and frontend/API consumers.

For every participating module, establish from repository evidence:

- the business decision it currently implements;
- the models/tables it reads and writes;
- the concrete operations other modules request from it;
- its incoming and outgoing module imports;
- existing reciprocal dependencies;
- the public behavior and failure/result shape that must remain stable.

If ownership cannot be proven, stop and report the unresolved decision. Do not assign ownership from a folder name alone.

## Dependency Rules

- New code outside a module must not import its controllers, requests, services, handlers, loggers, registries, factories, persistence helpers, traits, or other implementation classes.
- Cross-module access must use a narrow contract deliberately owned by the providing module, an established stable value/enum already serving as public vocabulary, or a published fact event when event semantics are correct.
- Do not add a reverse dependency to make implementation convenient.
- Do not use `app/Game/Core`, `app/Flare`, `Shared`, `Common`, helpers, traits, facades, service location, or global functions as a route around a module boundary.
- `Core` is allowed only for stable game-wide concepts that have the same meaning everywhere and do not depend on a feature module.
- A providing module must not depend on a consuming module merely to shape the consumer's response.
- Module providers wire their own implementations. Do not register a module contract in another module's provider.
- Direct calls remain preferred when the caller needs an immediate result. Do not replace visible control flow with events solely to avoid an import.

## Touched-Area Rule

Do not attempt a repository-wide rewrite during ordinary feature work. For the touched use case:

1. preserve behavior and external contracts;
2. do not add new forbidden imports;
3. reduce a concrete existing dependency when required for the change;
4. add a focused architecture test after a dependency is removed;
5. leave unrelated legacy dependencies unchanged and explicitly report them when relevant.

File moves without dependency or ownership changes are not an architectural improvement.

## Prohibited Architecture

Do not introduce:

- microservices or network boundaries;
- database transactions or shared cross-module transactions;
- after-commit dispatch or callbacks;
- transactional listeners/jobs;
- an outbox, saga, or compensation framework;
- repositories, command buses, ports/adapters, or interfaces for every class;
- a generic module facade containing unrelated operations.

Any exception requires explicit user authorization for that exact architectural mechanism.

## Completion Check

Before finishing, state which module owns the changed operation, list its permitted cross-module dependencies, confirm no new reverse dependency was introduced, and run the focused architecture and behavior tests authorized by the task.
