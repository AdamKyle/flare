---
name: back-end-method-control-flow
description: Use when writing or refactoring PHP methods to enforce guard-first flow, small responsibilities, minimal variable setup, semantic extraction, and clean happy-path traversal.
---

# Back End Method Control Flow

## Core shape

A PHP method should read top-to-bottom as:

1. resolve only the state needed for the next decision;
2. guard/return immediately when the operation cannot continue;
3. add a blank line;
4. continue the happy path;
5. return the result.

Do not collect a large block of variables at the top of a method before discovering that an early guard makes most of those values irrelevant.

Bad:

```php
$amount = $progress['amount'];
$count = $progress['count'];
$type = $progress['type'];
$itemId = $progress['item_id'];

if ($count >= $amount) {
    return $completed;
}
```

Better:

```php
$amount = $progress['amount'];
$count = $progress['count'];

if ($count >= $amount) {
    return $completed;
}

$type = $progress['type'];
$itemId = $progress['item_id'];
```

Only resolve values when the method has reached the point that actually needs them.

## Guard clauses

Use early returns for:

- missing domain state;
- unavailable records;
- already-completed work;
- permission/eligibility failures;
- capacity failures;
- no-op states;
- known service failures.

Do not bury the successful path under nesting.

Do not use `elseif` ladders for guard outcomes.

## Group related preconditions semantically

When several checks jointly answer one domain question, do not leave a long low-level condition in the main method.

Extract a small private method only when it gives the condition a meaningful name or returns a value needed by the happy path.

Examples:

- `hasReachedRequestedAmount(...)` returning `bool`;
- `resolveCraftableItem(...)` returning `?Item`;
- `resolveOutputDestination(...)` returning a typed enum/value or `null`;
- `resolveRetainedDestination(...)` returning the resolved collaborator/value needed later.

Do not extract meaningless helpers such as `checkThing()` or wrappers around one obvious line.

When different invalid states require different outcomes, use one small resolver/validator per real concern and let the caller guard on its result. Do not create one giant `validateEverything()` method returning an ambiguous array.

## One level of abstraction

A method should not simultaneously:

- parse/validate input;
- query several unrelated models;
- calculate preview/cost/capacity data;
- mutate persistence;
- fire events/messages;
- construct API responses.

If a touched method mixes several of these concerns, split it along real responsibilities.

A private method should itself remain small; extracting a 100-line block into a private method does not fix the design.

## Large preview/build methods

Preview/build methods must not become dumping grounds.

Separate real concerns such as:

- selected-item resolution;
- affordability calculation;
- destination capacity;
- blocker construction;
- final response assembly.

Use small typed/local values and focused private methods. Keep the top-level preview method readable as orchestration of those concerns.

Do not duplicate the same rules between preview and start. Reuse the same underlying domain calculation.

## Class constants

Do not introduce implementation-detail class constants in controllers, services, handlers, factories, or orchestrators for messages/copy or local workflow values.

For a repeated human-facing/service message, prefer a small private method returning the string when reuse improves readability.

For a closed domain value, use an enum.

For environment/configuration values, use configuration.

For a true framework-required/public contract constant, follow the framework/project contract.

Do not use a private class constant merely to avoid repeating one short string.

## Boolean and null flow

Use positive semantic names and guard on the outcome.

Prefer:

```php
$item = $this->resolveCraftableItem(...);

if (is_null($item)) {
    return ...;
}

$this->craft(...);
```

over repeated low-level checks in the caller.

Do not build a generic validation mini-framework. Keep each method specific to the domain operation.
