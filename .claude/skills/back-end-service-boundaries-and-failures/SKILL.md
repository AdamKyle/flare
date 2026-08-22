---
name: back-end-service-boundaries-and-failures
description: Use for Laravel service/handler boundaries to enforce trusted validated types, single-boundary normalization, ResponseBuilder results, logging/reporting, and non-throwing service contracts.
---

# Back End Service Boundaries and Failures

## Trust established boundaries

Do not repeatedly revalidate scalar types inside services/handlers after the value has already crossed a project-owned typed boundary.

Examples of project-owned boundaries include:

- FormRequest validation;
- typed DTO/value objects;
- backed enums;
- Eloquent model casts;
- JSON data originally persisted from validated request data without type-changing transformation;
- typed method parameters/return values.

Once the application has established the contract, downstream code should work with that contract.

Do not scatter defensive checks such as:

```php
is_int($value)
is_string($value)
is_bool($value)
```

through services/handlers solely because an array lookup technically has a mixed PHP type.

If the data is genuinely untrusted/legacy and its type cannot be guaranteed, normalize or validate it ONCE at the owning boundary and return a typed value/result. Do not repeat the same type archaeology in every downstream method.

Laravel validation verifies input; it is not permission to make assumptions about arbitrary external data. The rule is to establish the type contract once at the boundary and then trust that contract internally.

## Null versus invalid type

When project data is defined as `T|null`, code for `T|null`.

Do not write branches for unrelated scalar types that the established contract cannot produce.

If null means unavailable, check null and return the appropriate domain outcome.

If a value is required by a validated request and persisted by the feature, downstream code should not act as though it may randomly become a string/array/bool unless repository evidence proves that corruption/legacy compatibility is real and must be handled.

## Service methods do not throw

Application service methods must not intentionally throw or rethrow exceptions as part of their public contract.

This rule also applies to public module contracts. Do not copy exception-based module API examples from external architecture material into Flare. Use the established `ResponseBuilder` result or a typed result/value object owned by the providing module.

Do not write:

```php
try {
    ...
} catch (Throwable $throwable) {
    $this->markFailed();

    throw $throwable;
}
```

When a service can handle the failure, handle it completely:

1. log/report the unexpected failure through the existing project infrastructure;
2. preserve useful identifiers/context;
3. update domain/lifecycle state to a safe failed outcome when required;
4. return the service's established failure result or return normally for a void lifecycle method.

Do not catch and rethrow from a service after already handling the failure.

Expected domain failures are not exceptions. Return:

- `errorResult(...)` for API-facing service operations using `ResponseBuilder`;
- a typed operation/result enum/value object for internal domain/application workflows;
- `null` only when null is the established return contract for a lookup.

## Background/queued service work

For long-running/queued workflows, the service owns its domain lifecycle failure state.

Unexpected failures must be:

- logged with `Log` using specific context;
- reported through the existing monitored bug-report service where the project requires monitored reporting;
- converted to the workflow's FAILED/end state;
- returned from the service without rethrowing.

The queue job should stay thin and invoke the service. Do not make a service throw just so the job can fail after the service already recorded failure.

If framework retry behavior is explicitly required by a task, model that at the job boundary and document the intentional contract; do not silently change a service into a throwing API.

Do not introduce a database transaction, after-commit callback, transactional listener/job, or outbox as a failure-handling mechanism unless the user explicitly authorizes that exact architecture.

## ResponseBuilder

API-facing application service methods that can succeed/fail use the project's `App\Game\Core\Traits\ResponseBuilder` pattern when that is the established module convention.

The service returns:

- `successResult(...)`; or
- `errorResult(...)`.

The controller does not reconstruct business failures. It extracts the returned HTTP status and returns the payload.

Internal helper methods should return typed/local domain values, not ResponseBuilder envelopes.

## Avoid double-validation

Do not validate the same requirement independently in:

- FormRequest;
- controller;
- service;
- handler;
- job.

Validate syntax/input shape at the request boundary.

Validate mutable domain facts where they matter (for example current inventory capacity, current gold, current eligibility).

Do not re-check immutable request scalar types throughout the workflow.

## Failure messages

Player-facing errors must be direct and specific.

Operational/exception detail belongs in logs/monitoring, not in the response.

Do not use a class constant for service error/success copy merely to centralize a string. If a message must be reused in one class, use a clearly named private method returning the message, or use the owning translation/message mechanism if the project has one.
