---
name: phpunit-failure-remediation
description: Use this skill when diagnosing and correcting PHPUnit errors, failures, risky tests, notices, regressions, and performance outliers.
---

# PHPUnit Failure Remediation

## Purpose

Fix the production or test-contract root cause of every reported PHPUnit problem.

One shared defect may cause many failing tests. Diagnose failures by common stack trace, production path, exception, and output contract before changing individual assertions.

## Required process

For each affected test:

1. Read the complete test.
2. Read the complete production path.
3. Read the stack trace and actual assertion difference.
4. Identify whether the defect belongs to:
   - Production behavior.
   - A stale test contract.
   - Fixture construction.
   - Dependency registration.
   - Serialization or transformation.
   - Queue model rehydration.
   - Nondeterministic behavior.
   - Output typing.
   - A PHPUnit or Mockery test mistake.
5. Fix the authoritative layer.
6. Run the exact affected test class.
7. Confirm related failures sharing the root cause are resolved.
8. Do not hide the problem with a broader assertion.

## Production versus test decisions

Fix production when:

- A runtime method calls a removed API.
- A service provider is missing.
- A current public response violates its intended contract.
- A current transformer receives an unenriched object.
- A duration has an incorrect sign or public type.
- A current service consumes an outdated return type.
- A valid workflow incorrectly falls into a generic failure path.
- A lock or transaction design causes an avoidable timeout.
- A required view section is missing.
- Current schema relationships are used incorrectly.

Fix the test when:

- It calls a PHPUnit API on the wrong object.
- It asserts against the wrong response nesting level.
- It constructs an impossible or invalid relational fixture.
- It passes the wrong current constructor shape.
- It expects strict object identity across a queued-job serialization boundary.
- It creates duplicate rows prohibited by a deliberate unique constraint.
- It uses an obsolete column name.
- It instantiates a service without its required dependencies.
- It expects a float where the public database/API contract deliberately returns an integer, or vice versa.

Do not change a test merely because production currently returns a different value. Confirm the authoritative contract first.

## Prohibited shortcuts

Do not:

- Delete a failing test.
- Mark a test skipped or incomplete.
- Add `@doesNotPerformAssertions`.
- Add `#[DoesNotPerformAssertions]`.
- Add `#[AllowMockObjectsWithoutExpectations]`.
- Suppress PHP errors, exceptions, warnings, notices, or deprecations.
- Replace exact assertions with truthy assertions.
- Replace `assertSame()` with `assertEquals()` merely to hide a type defect.
- Catch an exception in production merely to make a test pass.
- Increase a database lock timeout.
- Add sleeps.
- Disable foreign-key constraints.
- Weaken a unique constraint.
- Restore removed model methods solely for legacy tests.
- Add methods back to an Eloquent model when the current architecture uses an enricher.
- Mock the class whose business behavior the test is verifying.
- Add direct Laravel model factories to test classes.
- Add private, protected, or public test helpers.
- Add data providers or loops to combine independent failures.
- Run coverage during failure remediation.

## Shared failures

When many tests fail through one stack trace:

- Fix the shared production boundary first.
- Rerun all classes using that boundary.
- Do not patch every test independently.
- Do not add defensive null fallbacks that conceal the underlying failure.
- Preserve public payload keys and business behavior.

## Queue jobs

Queued jobs rehydrate Eloquent models.

Mock expectations must compare model identity using model IDs or `Model::is()` rather than PHP object identity.

Execute jobs through ordinary `JobName::dispatch(...)`.

Do not return to direct `handle()` execution.

## Risky tests

A test with only Mockery expectations still requires an observable PHPUnit assertion when PHPUnit reports it as assertion-free.

Tests and production code must restore any custom error or exception handlers they install.

Do not silence risky-test reporting.

## PHPUnit notices

Replace an expectation-free mock with:

- A stub when no interaction matters.
- A configured expectation when interaction is part of the behavior.
- A real collaborator when isolation is unnecessary.

Do not use an opt-out attribute.

## Performance outliers

For a test taking tens of seconds:

- Identify the exact query, lock, recursion, external boundary, or timeout.
- Fix the underlying path.
- Do not raise timeout values.
- Do not skip the test.
- Do not mock away the behavior that the test owns.
- Record its runtime before and after.

## Completion

The task is complete only when:

- Every targeted class passes.
- The full suite passes.
- PHPUnit reports zero errors.
- PHPUnit reports zero failures.
- PHPUnit reports zero risky tests.
- PHPUnit reports zero notices.
- No test was deleted or suppressed.
- The final proof records the factual commands and results.
