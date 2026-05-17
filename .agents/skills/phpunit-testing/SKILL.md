---
name: phpunit-testing
description: Use this skill when writing, reviewing, or improving PHPUnit tests in this Laravel project, especially automation, faction loyalty automation, automation handlers, automation loggers, and coverage-focused test work.
---

# PHPUnit Testing Standards

- Write factual tests based only on code in this repository.
- Do not assume behavior.
- Do not invent methods, classes, factories, enum cases, properties, database columns, or relationships.
- Inspect the target class, dependencies, value objects, enums, models, factories, traits, and nearby existing tests before writing tests.
- Follow existing patterns in the closest matching test directory.
- Prefer real services, models, factories, traits, and database state.
- Do not mock anything unless required for random behavior, external side effects, or impossible state setup.
- Use Mockery when mocking is required.
- Mock random number generators only when randomness must be controlled.
- Do not mock Eloquent models or relationships.
- Do not mock factories.
- Do not mock the class being tested.
- Do not use private test helper methods.
- Do not use protected test helper methods.
- Do not add helper functions to test classes.
- Each test must test one behavior.
- Multiple assertions are allowed only when they validate the same returned object, persisted record, or single behavior.
- Do not combine unrelated success and failure paths in one test.
- Use public setUp(): void and public tearDown(): void.
- Null test class properties in tearDown().
- Use descriptive property names and variable names.
- Never use single-letter variables.
- Do not add inline comments.
- Do not remove existing comments.
- Do not add narrative comments.
- Do not add declare(strict_types=1).
- Do not make test classes final.
- Use RefreshDatabase when the test writes to the database.
- Use existing setup factories and traits instead of manually building large object graphs.
- For automation tests, inspect tests/Unit/Game/Automation first.
- For faction loyalty automation, prefer FactionLoyaltyFactory when available.
- For character setup, prefer CharacterFactory when available.
- For automation logger tests, create the real automation record, build the real result value object, call log(), then assert the persisted FactionLoyaltyAutomationLog data.
- Logger tests should verify creating a log row, appending to existing logs, preserving unrelated log arrays, updating the correct log array, and matching persisted payload fields.
- Use Carbon::setTestNow() when asserting created_at values from now().
- Reset Carbon::setTestNow() in tearDown() if used.
- Show full test file code when asked.
- Do not provide git diffs.
- If tests are not run, say they were not run.
- If a command cannot be run, say exactly why.

## Feature Controller Testing Rules

- For controller feature tests, write the happy path for each controller action by default.
- Do not duplicate service unit-test coverage in feature tests.
- Add failure-path feature tests only when the controller action itself contains validation, branching, authorization checks, or direct response logic.
- If the controller action has explicit conditional responses, test those response paths.
- Assert HTTP status and response message.
- Assert only the minimal persisted side effect needed to prove the request reached the intended application behavior.
## Reflection And Helper Method Rules

- Never use `ReflectionClass` in tests.
- Never use `ReflectionMethod` in tests.
- Never call `getMethod()` to access private or protected methods.
- Never call `setAccessible(true)`.
- Never directly test private methods.
- Never directly test protected methods.
- Test private and protected behavior only through the public API of the class being tested.
- For jobs, handlers, services, coordinators, and loggers, drive behavior through the public method that real code calls.
- Do not force coverage by testing implementation details.
- If a private/protected branch cannot be reached through public behavior, do not use reflection to cover it.
- Report the unreachable line or branch and explain why it cannot be covered without changing app code.
- 100% coverage must never be achieved by violating test standards.

## Mockery Rules

- Use Mockery only when a real dependency cannot reasonably be used, when orchestration branches need controlled dependency responses, when randomness must be controlled, or when external side effects must be prevented.
- Do not mock the class being tested.
- Do not mock Eloquent models.
- Do not mock Eloquent relationships.
- Do not mock factories.
- Mock service, coordinator, handler, logger, cache, queue, event, or randomizer dependencies only when the test requires controlled behavior.
- Mock protected methods with Mockery only when an existing dependency requires it and there is no clean public API path.
- Do not use Mockery protected-method mocking as a shortcut around normal behavior.
- Prefer real models, real factories, real value objects, and real persisted database state.
- Mocked expectations must match real method signatures and real return types.
- Do not invent mocked methods that do not exist in the code.
- Use `Mockery::mock()` for mocked dependencies.
- Use `shouldReceive()` only for methods actually called by the tested public behavior.
- Use `andReturnSelf()` only when the real method is fluent.
- Use `andThrow()` only for explicit exception-path tests.
- Mock one behavior per test unless multiple mocked calls are required for the same behavior path.