---
name: phpunit-suite-reduction
description: Use this skill when reducing, consolidating, reviewing, or removing PHPUnit tests in this repository.
---

# PHPUnit Suite Reduction

## Purpose

Reduce the number and cost of tests while retaining meaningful behavioral confidence.

The number of tests is not a quality metric by itself.

Keep tests for distinct business behavior, production branches, boundaries, failure states, mutations, authorization rules, recovery behavior, and contracts.

Remove tests that repeat the same production path without providing materially different confidence.

## Authoritative Test Layer

Each business rule must have one primary authoritative test layer.

Use these ownership rules:

- Services own calculations, validation rules, business decisions, state transitions, inventory mutations, currency mutations, ownership behavior, retries, recovery, and idempotency.
- Controllers own authentication, authorization, request validation, endpoint status, response shape, and one successful delegation or dispatch path.
- Jobs own dispatch and unique orchestration behavior.
- Events own their payload, channel, and broadcast contract.
- Listeners own their unique reaction to an event.
- Commands own argument handling, delegation, exit behavior, and user-visible command results.
- Transformers and presenters own meaningful output transformations and optional-value branches.
- Models own only custom model behavior, custom scopes, meaningful accessors, meaningful mutators, and business-specific casts.
- Value objects and enums own meaningful mappings or behavior, not assertions that PHP returns the constant that was declared.

Do not test every business permutation at every layer.

## Keep

Keep tests for:

- Each materially different public business behavior.
- Each production conditional branch with a meaningfully different result.
- Each authorization or ownership boundary.
- Each destructive operation.
- Currency debits, credits, caps, insufficient-funds behavior, and prevention of duplicate rewards.
- Inventory additions, removals, capacity boundaries, ownership, destination differences, and prevention of duplicate items.
- Completion, cancellation, continuation, timeout, retry, recovery, and idempotency.
- Boundary values such as below, equal to, and above a threshold when those values execute different behavior.
- Distinct handlers, destinations, strategies, or workflows when production uses different code.
- Required response and event contracts.
- Random-number behavior where controlled random output is required.
- Critical security behavior.
- One upper-layer integration path where lower-level behavior is already tested.

## Remove

Remove tests that:

- Repeat a service rule in controllers, jobs, commands, events, or listeners.
- Test the same branch using many values that produce the same result.
- Test every cross-product of type, destination, disposition, status, or input when the combinations execute the same production path.
- Prove Laravel relationships, ordinary casts, factory defaults, model persistence, constructors, property assignment, framework validation mechanics, or framework routing behavior without application-specific behavior.
- Assert that an enum case equals its declared scalar value without additional behavior.
- Assert every field repeatedly across multiple layers.
- Repeat the same event, message, log, or transformed payload for every caller.
- Test implementation details rather than public behavior.
- Exist only to increase assertion or coverage counts.
- Use reflection or another visibility bypass to test implementation details.
- Only resolve concrete classes or exercise provider `register()`, `boot()`, or `provides()` plumbing.
- Assert constructor/property assignment, declared enum values, or a thin third-party factory's returned class without an application-owned contract.
- Use tautological assertions such as `assertTrue(true)`.
- Recreate an already-authoritative scenario using a more expensive HTTP or job path.
- Exercise the same formula using arbitrary examples when representative boundaries cover the formula.
- Verify job reconstruction or reinitialization that the application does not perform.
- Directly call a job handler when the expected application path dispatches the job.

## Reduction Rules

For each test being reviewed:

1. Read the test.
2. Read the public production path it exercises.
3. Identify the exact branch, mutation, contract, or boundary it covers.
4. Find other tests covering the same behavior.
5. Select the lowest stable authoritative layer.
6. Keep one focused test for each materially distinct behavior.
7. Keep only thin integration coverage in upper layers.
8. Remove equivalent permutations.
9. Do not merge unrelated behaviors into a large test.
10. Do not use loops or data providers to hide the old test count.
11. Do not retain a test solely because it already exists.
12. Do not remove a unique critical behavior solely to reach a numeric target.

The suite-wide target may guide the audit, but a quota alone is never the reason recorded for deleting a test. The reason must be duplication, equivalent branching, framework-only coverage, unnecessary permutation coverage, or lack of meaningful behavioral value.

## Assertions

Each test must verify one behavior.

Multiple assertions are allowed when they verify the same behavior, mutation, response, event, or object state.

Do not combine independent business behaviors into one test to lower the test count.

## Coverage

Do not pursue 100% line coverage during a suite-reduction task unless explicitly instructed.

Do not add tests merely to restore a numeric coverage percentage after removing repetitive tests.

Preserve general behavioral coverage and critical paths.

Never claim a coverage percentage without running coverage.

## Test Execution

When a task explicitly prohibits test execution:

- Do not run PHPUnit.
- Do not run coverage.
- Do not run tests indirectly through another command.
- Do not claim retained tests pass.
- Complete the reduction using static production-path inspection.
- State clearly in proof of work that execution remains unverified.

## Exact Completion Standard

A repository-wide reduction task is incomplete if only one area was reduced.

Continue through all major test areas until the requested suite-wide range is reached or a factual blocker prevents safe completion.
