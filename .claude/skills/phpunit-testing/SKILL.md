---
name: phpunit-testing
description: Use this skill when writing, reviewing, or refactoring PHPUnit tests in this repository.
---

# PHPUnit Testing

## Scope

Use this skill for PHPUnit tests only.

Do not use this skill for production PHP/Laravel app code. Those rules belong in the `back-end-conventions` skill.

Before writing or changing a test, inspect the existing test class, nearby tests in the same module, relevant factories, models, value objects, enums, and the production code under test.

## Test Structure

* One behavior per test method.
* Inline all setup directly inside each test method body.
* Do not add private, protected, or public helper methods to a test class.
* Do not extract shared setup into a method and call it from multiple tests.
* Do not use PHPUnit data providers.
* Do not use loops inside a test to cover multiple independent behaviors or inputs.
* If two scenarios are independent behaviors, write two separate test methods, not one test with a loop or a data provider.
* A test method name should describe the single behavior being verified.

## What Not To Test

* Do not call private or protected methods directly from a test.
* Do not use reflection to bypass visibility, invoke private/protected methods, or read private/protected properties.
* Test behavior through the class's public API only.

## Mocking

* Use Mockery only where the existing project test patterns already use it for that class or collaborator.
* Do not introduce Mockery mocking for classes the surrounding tests exercise concretely (models, factories, value objects).
* Prefer real model/factory instances over mocks when the existing test patterns in that module do so.

## Assertions And Fixtures

* Use model factories from `database/factories` to build test data.
* Keep assertions specific to the single behavior under test.
* Do not assert on unrelated side effects that are not part of the behavior being verified.

## Running Tests

* Run each required targeted test command exactly once.
* Do not run the same test command repeatedly.
* Do not run test loops such as `for i in 1 2 3; do ...; done` around a test command.
* Do not run the full suite unless explicitly asked.
* Do not run coverage unless explicitly asked.
* If a required command fails, make one relevant fix, then rerun that command exactly once more.
* Do not rerun a passing command again just to prove stability unless explicitly asked.

## Output Rules

* Do not claim a test command was run unless it was actually run.
* If a command cannot be run, say exactly why.
* Report actual pass/fail results, not assumed results.
