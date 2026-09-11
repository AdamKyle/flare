---
name: back-end-method-documentation
description: Use for every PHP application-code change to enforce the repository's exact simple method-docblock format, constructor parameter documentation, and accurate method responsibility documentation.
---

# Back End Method Documentation

## Scope

Use this skill for PHP application code under `app/**`, route/provider classes, commands, jobs, events, listeners, controllers, requests, services, handlers, factories, registries, value objects, traits, and Eloquent models.

This skill does not require docblocks on PHPUnit test methods; PHPUnit documentation rules remain owned by the PHPUnit skills.

## Every touched application method is documented

Every method in a touched PHP application class must have a docblock immediately above it.

This includes public, private, permitted protected, static, framework/magic methods, and constructors.

Do not leave undocumented methods in a touched application class. Clean existing missing method documentation in the touched class as part of clean-as-you-go.

## Constructors use the exact simple format

Constructor docblocks contain ONLY one `@param` tag per constructor parameter.

Do not add:

- a constructor summary;
- parameter descriptions;
- alignment padding;
- prose such as `Existing service.` or `Create a new service instance.`.

Use exactly one ASCII space between the type and variable:

```php
/**
 * @param AdminGemRollService $adminGemRollService
 * @param AdminGemRollTransformer $adminGemRollTransformer
 * @param BuildMonsterCacheService $buildMonsterCacheService
 */
public function __construct(
    private readonly AdminGemRollService $adminGemRollService,
    private readonly AdminGemRollTransformer $adminGemRollTransformer,
    private readonly BuildMonsterCacheService $buildMonsterCacheService,
) {}
```

If a constructor has no parameters, do not add a parameterless constructor merely to satisfy this rule.

## Non-constructor methods use simple native/project types

Every non-constructor method docblock starts with one concise sentence describing WHAT the method does in domain/application terms.

After the summary:

- document every parameter with `@param`;
- document the return contract with `@return`, including `@return void` for void methods;
- do not add prose descriptions to `@param` tags;
- use the simple declared/native/project type in PHPDoc;
- do not document array shapes or generic collection element types.

Use the repository's non-constructor tag spacing:

```php
/**
 * Paginate the Map Gems list for the validated Admin index request.
 *
 * @param  MapGemIndexRequest  $request
 * @return LengthAwarePaginator Paginated
 */
public function paginate(MapGemIndexRequest $request): LengthAwarePaginator
```

For arrays, keep the return tag simple:

```php
/**
 * Build the internal Admin Map Gem form option data.
 *
 * @return array
 */
public function formOptions(): array
```

The following are prohibited in touched method PHPDoc:

- `array{...}` shapes;
- `Collection<int, Model>` or other collection generics;
- `array<int, Foo>` or other array generics;
- PHPStan/Psalm pseudo-object shapes;
- parameter prose after `$variable`;
- constructor parameter prose;
- verbose return-shape descriptions that restate every field.

PHP signatures, typed value objects, interfaces, transformers, definitions, and tests own exact contracts. PHPDoc remains concise and readable.

## Exceptions

Do not add `@throws` to service methods because services must not expose throwing as their contract.

For a non-service method that intentionally throws as part of its established contract, add `@throws` only when the exception can actually escape the method and the caller benefits from knowing it.

Do not document exceptions that are caught internally.

## Documentation quality

Good summaries describe responsibility:

- `Resolve the configured output destination for a retained crafted item.`
- `Complete the active Batch Crafting run with the supplied end reason.`
- `Return the currently visible Batch Crafting record for the character.`

Bad summaries merely repeat syntax:

- `This method builds preview.`
- `Gets the item.`
- `Handles the handler.`
- `Constructor.`

Keep docblocks accurate when method behavior changes. A stale docblock is a code defect.

Do not put implementation history, ticket references, temporary notes, debugging details, future plans, or architectural essays in method docblocks.

## Universal simple PHPDoc type rule

The repository never documents generic/type-internal detail in method PHPDoc.

Use only:

`@param Type $variableName`

`@return Type`

This applies to every kind of type, including:

- arrays;
- Collections/Eloquent Collections;
- Builders/Paginators;
- models/classes;
- value objects;
- enums;
- callbacks/callables;
- iterables;
- nullable/union conceptual types where PHPDoc is genuinely needed;
- every other documented method parameter or return value.

Never use:

- `array<string, int>`;
- `array<int, Foo>`;
- `array{...}`;
- `Collection<int, Item>`;
- `EloquentCollection<int, Gem>`;
- `Builder<Item>`;
- `Paginator<Monster>`;
- `class-string<Foo>`;
- callable signatures such as `callable(string): int`;
- `@template`;
- `@phpstan-type`;
- `@psalm-type`;
- parameter descriptions after `$variableName`;
- return descriptions after the simple type.

Use imported/simple class names rather than fully qualifying a generic-looking documentation type.

Do not align tags with repeated spaces. Constructor and method tags use one normal ASCII space between tag, type, and variable.

Correct:

```php
@param array $earnedCurrencies
@param Collection $items
@param Character $character
@return array
@return Collection
```

Incorrect:

```php
@param  array<string, int>  $earnedCurrencies
@param Collection<int, Item> $items
@return array<string, mixed> Description
```

If a docblock becomes useless after removing type-detail noise and the governing method-documentation rule does not require additional prose for that method category, remove unnecessary noise rather than replacing it with another type-description mechanism.
