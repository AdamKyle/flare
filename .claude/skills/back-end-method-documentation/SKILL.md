---
name: back-end-method-documentation
description: Use for every PHP application-code change to enforce consistent method docblocks, constructor documentation, parameter/return documentation, and accurate method contracts.
---

# Back End Method Documentation

## Scope

Use this skill for PHP application code under `app/**`, route/provider classes, commands, jobs, events, listeners, controllers, requests, services, handlers, factories, registries, value objects, traits, and Eloquent models.

This skill does not require docblocks on PHPUnit test methods; PHPUnit documentation rules remain owned by the PHPUnit skills.

## Every class method is documented

Every method in a touched PHP application class must have a docblock immediately above it.

This includes:

- public methods;
- private methods;
- protected methods when a protected method is explicitly permitted;
- static methods;
- magic/framework methods such as `boot`, `register`, `handle`, `rules`, `authorize`, `newFactory`, and relationship methods;
- constructors.

Do not leave undocumented methods in a touched application class. Clean existing missing method documentation in the touched class as part of clean-as-you-go.

## Constructors

Constructor docblocks contain ONLY `@param` tags.

Do not add a constructor summary or description.

Do not add prose such as `Create a new service instance.`

Example:

```php
/**
 * @param CraftingService $craftingService
 * @param BatchCraftingSetService $batchCraftingSetService
 */
public function __construct(
    private readonly CraftingService $craftingService,
    private readonly BatchCraftingSetService $batchCraftingSetService,
) {}
```

If a constructor has no parameters, do not add a parameterless constructor merely to satisfy this rule.

## Non-constructor methods

Every non-constructor method docblock starts with one concise sentence describing WHAT the method does in domain/application terms.

The summary must add useful meaning. Do not narrate the implementation line by line.

Then document every parameter with `@param`.

Document the return contract with `@return`, including `@return void` for void methods.

Example:

```php
/**
 * Build the Batch Crafting preview for the validated Craft Amount request.
 *
 * @param Character $character
 * @param array $validated
 * @return array
 */
private function buildPreview(Character $character, array $validated): array
```

For a meaningful array shape, document the shape instead of writing only `array` when the shape is stable and useful to callers/static analysis.

For collection/generic types, use the project's existing PHPDoc generic syntax.

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

Do not put implementation history, ticket references, temporary notes, debugging details, or future plans in method docblocks.
