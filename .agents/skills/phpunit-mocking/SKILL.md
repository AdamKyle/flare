---
name: phpunit-mocking
description: Use this skill whenever PHPUnit tests replace collaborators, control nondeterministic behavior, intercept logging or broadcasting, or isolate orchestration boundaries.
---

# PHPUnit Mocking

## Purpose

Mocks are allowed when they isolate a legitimate boundary or control behavior that cannot be reliably or efficiently produced through the real collaborator.

The goal is not zero mocks.

The goal is to retain only mocks that have a clear testing purpose.

Do not remove a mock merely because the mocked class is a service.

## Allowed Mock Categories

Mocks are allowed for the following categories.

### Random and nondeterministic behavior

Mock random-number or random-selection behavior when the test requires a deterministic outcome.

Examples include:

- Skill checks.
- Character rolls.
- Drop checks.
- Affix selection.
- Item generation.
- Enchantment selection.
- Gem generation.
- Gambler spins.
- Random reward selection.

Partial mocks of the subject are allowed only when they replace a protected or public random-selection boundary and the remaining real implementation is the behavior under test.

Do not partial-mock unrelated internal logic.

### Map, tile, and image-backed validation

Mock map collaborators when the real implementation requires reading image files, pixel colors, water tiles, blocked tiles, or generated map files.

Approved examples include:

- `MapTileValue`
- `CanTravelToMap`
- Existing map color or tile validation collaborators

Do not generate fake map images merely to satisfy movement validation.

A test whose actual subject is image generation or storage output may use an isolated storage disk when the generated file itself is the behavior under test.

### Logging, broadcasting, mail, and error reporting

Mock or spy on Laravel facades and external-output boundaries when the behavior under test is:

- A warning, error, or information log call.
- A broadcast attempt.
- A mail dispatch.
- Failure handling for an unavailable output channel.
- Submission to monitored error reporting.

Do not read physical Laravel log files from a PHPUnit test.

Use the Laravel facade spy/mock pattern or the existing project-specific output abstraction.

Approved examples include:

- `Log::spy()`
- `Log::shouldReceive(...)`
- Broadcast failure doubles
- Mail fakes
- `MonitoredBugReportService` when verifying error-report delegation

### Orchestration collaborators

A service, handler, coordinator, or manager may be mocked when the subject under test owns orchestration rather than the collaborator’s underlying business behavior.

The mocked collaborator must have its meaningful behavior covered at its own authoritative layer.

Orchestration mocks may be used to:

- Return a specific result to the caller.
- Simulate a downstream success.
- Simulate a downstream failure.
- Verify arguments passed to a downstream service.
- Prevent an unrelated subsystem from executing.
- Prevent a synchronous queue from recursively running a large unrelated chain.
- Verify continuation, cancellation, retry, recovery, or idempotency owned by the caller.

Approved repository examples include:

- `BatchCraftingService` in `BatchCraftingJob` and `BattleEventHandler` tests.
- `CraftingService`, `EnchantingService`, `AlchemyService`, `TrinketCraftingService`, and `SkillCheckService` in `BatchCraftingProcessor` orchestration tests.
- `MonsterFightService` in automated-battle handler tests.
- `BattleEventHandler` in monster-fight and raid-battle tests.
- `CharacterRewardService` in reward queue, checkpoint, resume, and idempotency tests.
- `FactionLoyaltyService` in faction pledge cleanup tests.
- `KingdomEventService` in event-ending orchestration tests.
- `BattleRewardService` in reward job tests.
- Quest handlers in quest jobs.
- Fractal transformers and managers when testing an explicit transformation failure path.

These mocks are not inherently wrong.

They must be evaluated based on which class owns the behavior being tested.

### Failure boundaries

Mocks are allowed when a test must force:

- An exception.
- A timeout result.
- A missing downstream result.
- A retryable failure.
- A permanent failure.
- A failed broadcast.
- A failed log or error-report submission.
- An unavailable external adapter.
- A deleted model returned by a downstream collaborator.
- A partial-processing checkpoint.

Do not manufacture failures by modifying production code.

## Disallowed Mocks

Do not mock:

- Eloquent models merely to avoid creating fixtures.
- Value objects.
- Enums.
- Laravel factories.
- Test fixture factories.
- The class whose public business behavior is the subject, except for a narrowly controlled random-selection method.
- A real deterministic collaborator solely because arranging its input requires effort.
- A collaborator whose behavior is the exact business rule the test claims to verify.
- Internal implementation methods that are not random or external boundaries.
- A service solely to assert that one method was called when the meaningful observable result can be verified directly.

Do not use a mock to conceal an incorrectly designed test.

## Mock Audit Procedure

For every retained mock:

1. Read the test.
2. Read the subject under test.
3. Read the mocked collaborator’s public path.
4. Identify which class owns the asserted behavior.
5. Assign the mock to one allowed category.
6. Confirm the test verifies the caller’s behavior rather than re-testing the mocked class.
7. Keep the mock when it has a valid category.
8. Remove or replace it when it exists only for convenience.
9. Do not change production code.
10. Do not delete a meaningful test merely because it uses a mock.

There is no target Mockery count.

A high count is acceptable when every mock has a factual purpose.

## Jobs and Container Bindings

Jobs must execute through:

`JobName::dispatch($arguments);`

When a job test requires a mocked dependency:

1. Create the mock.
2. Bind it into Laravel’s application container using the existing test pattern, such as `$this->app->instance(ServiceClass::class, $mock)`.
3. Dispatch the job normally.
4. Allow the configured synchronous queue connection to execute it.

Do not manually call a job’s `handle()` method.

Selective queue fakes may be used when the subject job must execute but downstream jobs must not execute.

Do not fake the subject job itself.

Constructing a job without executing it is allowed when testing only constructor-owned metadata such as its queue or connection.

## Logging

Tests must not open or parse files under `storage/logs`.

Use Laravel logging spies or mocks when the log call is the behavior under test.

Application logs produced incidentally by another test are not assertions.

## Acceptance Standard

A completed mock audit must report:

- Starting and final `Mockery::mock` counts.
- Starting and final `shouldReceive` counts.
- Mocks retained by allowed category.
- Mocks removed and the factual reason.
- Direct job `handle()` execution remaining.
- Confirmation that no physical Laravel log files are read by tests.

Do not claim mocks were validated by execution when tests were not run.
