---
name: phpunit-testing
description: Use this skill when writing, reviewing, reducing, or refactoring PHPUnit tests in this repository.
---

# PHPUnit Testing

## Scope

Use this skill for PHPUnit tests and test infrastructure only. Also apply `repository-code-quality-and-clean-as-you-go`.

Production backend rules belong to the `back-end-conventions` skill.

Before changing a retained test:

- Read the test class.
- Read nearby tests in the same domain.
- Read the public production path under test.
- Read applicable traits and setup factories.
- Identify the exact behavior owned by that test.

Do not assume behavior from a test name.

## Test Structure

- Never declare test classes `final`; remove `final` when touching an existing test class.
- Existing test-rule violations in a touched test must be cleaned up instead of copied or preserved.
- One behavior per test method.
- Use a descriptive test name.
- Multiple assertions are allowed only when they verify the same behavior, mutation, response, event, or object state.
- Do not combine independent behavior to lower the test count.
- Do not use PHPUnit data providers.
- Do not use loops to cover multiple independent inputs or behaviors.
- Do not test private or protected methods.
- Do not use reflection.
- Exercise behavior through public APIs.

## Non-Public Access Is An Automatic Failure

Tests must contain zero uses of `ReflectionClass`, `ReflectionMethod`, `ReflectionProperty`,
`setAccessible()`, `invoke()`, or `invokeArgs()` to reach production internals. Do not use bound
closures, anonymous subclasses, visibility changes, or another indirect mechanism to execute or
inspect private/protected production state.

When a rule cannot be reached through a meaningful public path, cover it through the existing
public workflow, extract a genuinely independent rule into a focused public collaborator, or
simplify/remove an impossible branch. Never change production visibility only for a test.

## Assertion Integrity

Never use `assertTrue(true)`, `assertFalse(false)`, assertions on values created entirely by the
test, or another tautology to satisfy PHPUnit's assertion count. Tests must not exist only to prove
container resolution, provider `register()`/`boot()`/`provides()` plumbing, framework plumbing, or
direct constructor-to-property assignment.

## Shared Setup

Scenario-specific setup belongs in the test method and must use traits or domain setup factories.

`setUp()` may contain a small shared baseline only when every test in the class requires that exact baseline.

`setUp()` is not a mechanism for reusing one database record across tests. It runs before every test.

Do not place complicated fixture graphs, scenario-specific state, loops, or branching in `setUp()`.

Do not add private, protected, or public test helper methods.

Existing helper methods in retained test files must be removed or moved into the appropriate fixture abstraction.

## Fixtures

Test classes must not call Laravel model factories directly.

Use:

- Existing `Tests\Traits\Create*` traits for single models.
- Existing domain setup factories.
- Existing domain management classes.
- A narrowly scoped new trait or setup factory when one is missing.

Use `CharacterFactory` for normal playable characters and character-related graphs.

Do not manually recreate character inventory, skills, passive skills, automation, kingdoms, bags, class ranks, or attack data when the character setup APIs already own that behavior.

## Database

Keep `RefreshDatabase` for database-backed tests.

Do not truncate tables under `tests/**`.

Do not manually clear every model.

Do not manually clean database records after a test.

Rely on Laravel’s transactional rollback.

Use transactional `delete()` only when a test specifically requires a table empty before continuing inside that test.

## Jobs

Queue-job execution must use the real application dispatch path:

`JobName::dispatch($arguments);`

The following are all forms of prohibited manual queue-job execution:

- `$job->handle(...)`
- `app()->call([$job, 'handle'])`
- `$this->app->call([$job, 'handle'])`
- `Container::call([$job, 'handle'])`
- Invoking `handle()` through a closure
- Invoking `handle()` through reflection
- `dispatchSync(...)` when the application path uses ordinary `dispatch(...)`

Binding dependencies into Laravel’s container does not make a manual `handle()` call acceptable.

When a queue job requires mocked collaborators:

1. Create the mock.
2. Bind it into Laravel’s application container.
3. Call `JobName::dispatch(...)`.
4. Allow the configured synchronous testing queue to execute it.

Middleware, listeners, coordinators, and ordinary handlers may still have their public `handle()` API called directly.

The prohibition applies to classes implementing `ShouldQueue`.

Do not retain tests whose only purpose is to prove that a job reconstructs or recursively redispatches itself.

When a test manually executes a job only so it can stop before another copy is dispatched:

- Prefer a complete observable state assertion through normal dispatch.
- Remove queue-reinitialization metadata coverage when it provides no independent business confidence.
- Do not introduce a complicated fake dispatcher solely to preserve such a test.

## Real Path / Let It Fall Through

The default testing strategy is to let the real application code path execute.

Start from the public API owned by the subject and allow real deterministic collaborators to run unless an allowed mock category in `phpunit-mocking` applies.

Do not mock a collaborator merely because arranging the real state takes more work. Do not mock the class that owns the business rule being asserted.

For queued jobs, the real application path means dispatching the job and allowing the configured synchronous test queue to execute it.

For PHP attributes, follow `phpunit-php-attributes`: test the public framework/application behavior that consumes the metadata, not reflection or attribute presence.

## Mocking

Mocks are allowed under the categories defined by `phpunit-mocking`.

Ordinary services may be mocked when they are downstream orchestration collaborators.

Random, map, logging, broadcasting, error-reporting, and forced-failure boundaries may be mocked.

The class owning the asserted business rule must not be replaced by a mock.

There is no target mock count.

Physical Laravel logs must not be read by tests.

## Maps

Do not create fake map image files.

Do not use:

- `Storage::fake('maps')`
- `imagecreatetruecolor()`
- `imagecolorallocate()`
- `imagefill()`
- `ob_start()`
- `imagepng()`
- `Storage::disk('maps')->put(...)`
- `imagedestroy()`

Use the character’s existing map, create only necessary locations, use required cache setup, or mock the map validation dependency through the existing pattern.

## What Not To Test

Do not retain tests that only prove:

- Laravel relationships.
- Ordinary model persistence.
- Factory defaults.
- Basic constructors.
- Direct property assignment.
- Framework behavior without application-specific behavior.
- Enum constants equal their declared values.
- The same service rule through several upper layers.
- Equivalent values execute the same production branch.

## Running Tests

Follow the task’s command restrictions.

When the task forbids tests:

- Do not run PHPUnit.
- Do not run targeted tests.
- Do not run the full suite.
- Do not run coverage.
- Do not run tests indirectly.
- Do not claim passing tests or measured coverage.

## Output

Report only factual work performed.

Do not claim commands were run unless they were run.

Do not claim behavior was verified by execution when only static inspection occurred.

Before completion, search every touched test path for prohibited patterns. Zero matches are
required for reflection, non-lifecycle test helpers, assertion padding, direct model factories,
and manual queued-job execution.

## Deleted implementation tests

When production code is deleted because a legacy implementation has been replaced, delete tests that exist only to test that deleted implementation. Do not move giant legacy test classes into the new namespace and do not preserve implementation-detail tests for classes that no longer exist.

Write focused tests for the new public responsibilities and let the real deterministic path fall through.

## Coverage when explicitly required

When the task explicitly requires 100% coverage for new or migrated backend code, every executable line in the task-specific new/migrated production files must reach 100% before completion.

Inspect per-file coverage, not only aggregate coverage. Do not use `@codeCoverageIgnore`, meaningless framework tests, reflection-only tests, impossible mocks, loops, or data providers to manufacture the percentage. If a branch cannot occur under the real contract, simplify/remove the branch rather than inventing an artificial test path.

Do not claim a coverage percentage unless coverage was actually run and inspected.
