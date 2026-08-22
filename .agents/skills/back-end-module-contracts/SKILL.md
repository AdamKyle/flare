---
name: back-end-module-contracts
description: Use when one app/Game module needs to call or read another module, or when creating/reviewing backend interfaces, DTOs, value objects, public results, and module APIs.
---

# Back End Module Contracts

## Public Surface

A module exposes only operations required by proven callers. Public contracts belong to the providing module under its established structure; use `Contracts` and focused `Values` when the module does not yet have a public-contract convention.

Outside callers must not receive or depend on:

- an Eloquent model or collection of models;
- an Eloquent/query builder;
- internal services, handlers, factories, registries, transformers, traits, or persistence helpers;
- internal exceptions;
- an untyped associative array whose keys must be probed to determine the result.

## Contract Design

- Name contracts after a business capability or focused query/operation, not implementation technology.
- Keep each contract narrow. Do not create a fifty-method module facade.
- Accept identifiers, enums, and small typed command/value objects when the call crosses a real module boundary.
- Return a purpose-specific immutable snapshot or typed operation result containing only what the caller is permitted to know.
- The providing module owns its contract types and translates internal models into them.
- Contract namespaces must not import the providing module's implementation namespaces.
- Do not expose setters, persistence methods, relationships, cache keys, table names, or framework-specific query behavior.
- Preserve established external HTTP payloads by translating at the controller/API boundary.

## Failure Contract

Flare service and module contracts do not intentionally throw. Use the existing `ResponseBuilder` convention for API-facing operations when established, or a typed result/value object for internal workflows. Do not copy exception-based public API examples into this repository.

## Read Consistency

Use a synchronous contract when the caller needs current information. If a cached, projected, or delayed value is proposed, repository evidence and the task must establish that stale data is acceptable. Do not silently change consistency behavior.

## Dependency Injection

Bind an interface to its implementation in the providing module's service provider. Consumers inject the contract. Do not use `resolve()`, `app()`, a facade, a trait, or a static helper to bypass the contract.

## Tests

Test the providing module through the public contract. Consumer tests may fake the public contract when isolation is useful; do not mock every internal collaborator. Add an architecture test proving that consumers cannot import the newly protected implementation namespace.
