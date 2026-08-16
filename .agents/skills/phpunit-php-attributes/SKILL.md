---
name: phpunit-php-attributes
description: Use when testing Laravel or custom PHP attribute behavior while following the repository's real-path, fixture, and mocking rules.
---

# PHPUnit Testing for PHP Attributes

## Core rule: test behavior, not decoration

Do not write a test whose only purpose is to prove that PHP can attach an attribute or that an attribute constructor stores its arguments.

Do not directly inspect attributes with `ReflectionClass`, `ReflectionMethod`, `getAttributes()`, or equivalent reflection code in a test.

Exercise the public application/framework path that consumes the attribute and assert the observable behavior produced by that path.

## Let the real path fall through

By default, let the real code path execute from the public entry point through the real attribute consumer and its real deterministic collaborators.

Do not mock the attribute consumer when the consumer's behavior is what the test is meant to prove.

A mock is permitted only when it fits an allowed category in `phpunit-mocking`, such as:

- nondeterministic/random behavior that must be controlled;
- map/tile/image-backed boundaries;
- logging, broadcasting, mail, monitored error reporting, or another external/output boundary when that boundary is the asserted behavior or must be forced to fail;
- an orchestration collaborator whose underlying business behavior is authoritatively tested elsewhere;
- a forced downstream failure needed to exercise the caller's failure behavior.

Convenience is not an allowed reason to mock.

## Laravel first-party attributes

When Laravel itself consumes an attribute, test through the Laravel behavior that consumes it.

Examples of the testing shape:

- resolve the real class through Laravel's container and assert the injected/resolved behavior;
- invoke the real model/service/framework path that uses the metadata;
- make the HTTP request or public service call when that is the application entry point.

Do not replace a framework-consumed attribute test with a reflection assertion that the attribute is present.

## Custom attributes

A custom attribute requires a real application consumer.

Test the consumer's public API with a real declaration carrying the attribute and assert the resulting application behavior.

If a small test-only declaration is needed to exercise a generic consumer, place it in a dedicated test fixture/support abstraction consistent with the repository. Do not hide it in private/protected/public helper methods on the test class.

Do not create a custom attribute test when no production consumer exists. That indicates dead metadata, not missing coverage.

## Test ownership

Test each behavior at its authoritative layer.

- Attribute class: no constructor/property-assignment test.
- Attribute consumer: tests metadata interpretation and delegation behavior it owns.
- Domain service called by the consumer: tests its business rules separately through its public API.
- Controller/job/listener: keeps only the thin integration/orchestration behavior it owns.

Do not repeat every domain permutation through the attribute layer.

## Existing PHPUnit rules

All normal repository PHPUnit rules still apply:

- one behavior per test;
- public API only;
- no reflection;
- no test helper methods;
- use existing traits and domain setup factories;
- shared baseline belongs in `setUp()` only when every test needs the same baseline;
- scenario-specific setup remains visible in the test and uses fixture abstractions;
- no direct model factories in test classes;
- jobs execute through the real dispatch path;
- database-backed tests use the existing isolation pattern.

When an existing attribute-related test violates these rules and is touched, clean it up as part of the task.
