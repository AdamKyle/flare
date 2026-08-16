---
name: back-end-php-attributes
description: Use when adding, reviewing, consuming, or refactoring PHP attributes in this Laravel application.
---

# PHP Attributes in Laravel

## Baseline

This repository uses PHP 8.4 and Laravel 12.

A PHP attribute is structured metadata attached to a declaration. An attribute does nothing by itself. Laravel, PHP reflection infrastructure, or an explicit application consumer must read it for it to affect behavior.

Never add an attribute unless the code that consumes that metadata is known and inspected.

## When attributes are appropriate

Use an attribute when it makes a real declarative relationship clearer at the declaration it affects and a framework or focused application consumer already has responsibility for interpreting it.

Good categories include:

- first-party Laravel container/binding attributes when they replace otherwise-scattered container configuration cleanly;
- framework-supported model metadata when the repository is using that Laravel feature;
- small project-specific metadata consumed by one clearly named infrastructure/service path.

Do not use an attribute merely because the syntax is available.

Do not move ordinary domain business logic into attributes to make execution implicit.

Do not use attributes to hide authorization, validation, persistence mutations, or workflow side effects when an explicit service/request/policy path is clearer.

## Prefer first-party Laravel behavior

Before creating a custom attribute, inspect Laravel 12 and the repository for an existing first-party or project attribute that already represents the concern.

Laravel 12 provides first-party container attributes including `Bind`, `Singleton`, and `Scoped`, plus contextual dependency attributes such as `Auth`, `Cache`, `Config`, `Context`, `DB`, `Give`, `Log`, `RouteParameter`, `Storage`, and `Tag`. Laravel 12 also provides Eloquent metadata attributes such as `ObservedBy` and `ScopedBy`.

Use a first-party attribute only for the framework behavior it actually represents. Inspect the Laravel 12 contract and the current repository usage before applying it.

Do not mix provider/manual registration and attribute configuration for the same binding, observer, or scope without a factual reason.

## Custom attribute declaration

A custom attribute class should be small, typed metadata.

Rules:

- import PHP's `Attribute` class; do not use a leading-backslash fully qualified name in the declaration;
- declare the exact supported target with `Attribute::TARGET_*` flags;
- enable repeatability only when multiple instances on the same declaration are a real supported contract;
- use typed constructor parameters;
- prefer enums, class constants, and class names over magic strings/numbers;
- use `readonly` promoted properties when the metadata is immutable;
- never declare the attribute class `final`;
- do not add `declare(strict_types=1);`;
- do not perform I/O, database queries, container resolution, or domain work in the attribute constructor.

Example shape:

```php
use Attribute as PhpAttribute;

#[PhpAttribute(PhpAttribute::TARGET_METHOD)]
class ExampleMetadata
{
    public function __construct(
        public readonly ExampleType $type,
    ) {}
}
```

The example shows structure only. Do not create `ExampleMetadata` or `ExampleType` unless the real task requires them.

## Attribute usage

Keep the attribute directly beside the declaration it describes.

Use imported short class names rather than fully qualified attribute names in application code.

Prefer named arguments when several same-typed or optional arguments would otherwise be difficult to read.

Do not stack attributes until the method/class signature becomes harder to understand than explicit configuration.

An attribute argument must be static metadata supported by PHP attribute syntax; do not attempt to place runtime expressions or service calls in attribute arguments.

## Consuming custom attributes

Custom PHP attributes are normally discovered through PHP reflection. Reflection use must be centralized in the one application/infrastructure component responsible for interpreting that metadata.

Do not scatter `ReflectionClass`, `ReflectionMethod`, or `getAttributes()` calls across controllers/services.

The consumer should:

- inspect only the declaration types it owns;
- request the specific attribute class;
- validate/handle the supported metadata contract explicitly;
- delegate resulting business behavior to normal services;
- avoid turning metadata discovery into a hidden service locator.

If no consumer exists, the attribute is dead metadata and must not be added.

## Readability decision

Choose an attribute only when all of these are true:

1. The metadata belongs naturally beside the declaration.
2. A known consumer exists or is explicitly part of the task.
3. The attribute removes clearer boilerplate rather than hiding business flow.
4. The metadata contract can stay small and typed.
5. The resulting code is easier to understand by reading the declaration and the named consumer.

Otherwise use the repository's existing explicit Laravel/service configuration pattern.

## Cleanup

When touching existing attribute code, apply all repository rules. Existing `final`, leading-backslash imports, unnecessary comments/docblocks, prohibited casts, or scattered reflection are not precedent.
