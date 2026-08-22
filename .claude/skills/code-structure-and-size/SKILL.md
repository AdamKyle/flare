---
name: code-structure-and-size
description: Use when creating, changing, or refactoring production classes, controllers, services, routes, React components, hooks, or large tests so touched code does not accumulate multiple responsibilities.
---

# Code Structure and Size

## Size is a design signal

Do not optimize for the fewest files or the fewest lines.

A large file is acceptable only when it remains cohesive. A small file is not automatically good design.

When touching a large file, identify the exact responsibility being changed and whether that responsibility is already mixed with unrelated work. If it is, extract the touched responsibility into the repository's existing module structure instead of adding another branch to a do-everything class or component.

Do not create abstraction layers solely to reduce line count.

## Methods

A method should express one operation at one level of abstraction.

Extract or reorganize a touched method when it combines several of these concerns:

- input validation;
- domain decision making;
- persistence;
- unrelated calculations;
- queue/event/broadcast orchestration;
- response construction;
- logging/error reporting;
- multiple independent workflow phases.

Prefer descriptive methods and services over a single long method containing many nested branches.

Do not hide complexity in dense one-liners, nested ternaries, or chained callbacks that are harder to read than the original code.

## Backend classes

Controllers remain thin. A controller should validate/bind input through the project pattern, delegate to the owning application/domain service, and return the response.

Do not add domain calculations, persistence workflows, inventory mutations, crafting rules, queue state machines, or other business logic to controllers.

A service that coordinates several distinct workflow phases must delegate those phases to focused collaborators when the phases have independent rules or tests.

When a touched service has grown into a broad coordinator plus implementation for every phase, extract the phase being modified and its directly related logic. Keep orchestration visible in the coordinator.

Do not move module-specific logic into a generic/global folder simply to shorten a class.

Extracting a class does not create a module boundary. When extracted code is consumed by another game module, expose only the narrow owning-module contract required by the caller. Do not move the same coupling behind a deeper namespace.

## Routes

Route files declare routing and middleware only.

Do not add domain/business logic, database queries, or complex closures to route files.

Keep routes in the owning module route file and use that module's established controller syntax and middleware pattern.

If a route file contains unrelated domains, do not add another unrelated domain to it when an existing module route file can own the route.

## Frontend components

A visual component should not simultaneously own API contract construction, domain validation, websocket lifecycle, large state machines, and large rendering trees.

Use the existing feature hooks, API hooks, pure utilities, and child components to separate those responsibilities.

Do not extract one-line components or hooks solely to reduce file length.

Extract when a section has its own clear responsibility, props contract, state lifecycle, or reusable behavior.

## Hooks

A custom hook should own one coherent React concern.

Do not create a single hook that becomes a hidden controller for unrelated feature behavior.

Split a touched hook when it independently manages unrelated API actions, unrelated subscriptions, unrelated form domains, or unrelated state machines.

## Domain Result Shapes

Do not use associative arrays as pseudo-objects when callers must inspect arbitrary keys to determine status/type or when the same shape crosses several collaborators.

For new/refactored workflow internals, prefer an explicit enum discriminator and a small typed result/value object when that makes the contract clearer. Keep response arrays where they are the established external/API contract, and translate to them at the boundary.

Do not create a value object for a one-method local value that is already clear and typed; the purpose is to remove ambiguous dynamic shapes, not to maximize class count.

## Tests

Do not make a test file smaller by hiding scenarios in loops, data providers, helper methods, or giant fixture builders inside the test class.

Large test classes should be split only along real production ownership boundaries, such as distinct public methods, workflow phases, or materially separate behavior groups.

Every resulting test still follows the repository fixture, setup, mocking, and one-behavior rules.

## Touched-area requirement

If the code being changed is already structurally non-compliant, cleanup is part of the task. Do not add more logic and leave the same structural problem worse than before.

Preserve behavior while improving the touched area. Do not use a local task as justification for unrelated module-wide restructuring.

## Legacy replacement footprint

When replacing a legacy feature, the new implementation should have a materially smaller and clearer responsibility footprint. Do not move thousands of legacy lines into a new namespace, rename a giant service, or split one giant switch into many equally broad classes without removing duplicated responsibility.

Delete superseded production code and its implementation-detail tests when the task's architecture says the legacy implementation is replaced. Keep only database/model compatibility that is still required and explicitly outside the task's migration scope.
