---
name: back-end-data-ownership
description: Use when Laravel backend work reads or writes Eloquent models/tables across app/Game modules, adds relationships or queries, or evaluates model and persistence ownership.
---

# Back End Data Ownership

## Global Model Location Is Not Global Ownership

Eloquent models remain under `app/Flare/Models` unless the user explicitly requests a migration. Their physical location does not authorize every module to write every model.

Determine ownership from the implemented business rules, current mutation paths, routes, services, handlers, jobs, and tests. Do not infer ownership only from a model name or relationship.

## Required Ownership Decision

For every model/table newly mutated in touched code, identify exactly one owning game module or prove that it is truly global application data. If ownership is ambiguous or currently split across modules, stop and report the conflicting mutation paths before adding another writer.

The owner controls:

- creation and mutation rules;
- lifecycle and deletion behavior;
- write-side validation and invariants;
- public operations through which another module requests a change.

## Cross-Module Access

- A non-owner must not add direct `create`, `update`, `delete`, `save`, `increment`, `decrement`, bulk update, raw SQL write, or relationship mutation against the owner's data.
- Request foreign mutations through the owner's public module contract.
- For current foreign data, use a focused query contract returning a typed snapshot.
- Do not pass a writable model to another module so it can mutate owner data indirectly.
- Do not add a cross-module Eloquent relationship for convenience. Store/use identifiers and request data through the owner when a protected boundary has been established.
- Existing relationships may remain outside the touched scope. Do not expand their use or add eager-loading chains across a newly protected boundary.
- Reporting, tops, exports, and admin aggregation may require deliberate multi-model reads. Keep those reads read-only, localized, documented in the owning query service, and faithful to existing performance and freshness behavior.

## Explicitly Prohibited

Do not introduce database transactions, `DB::transaction()`, manual begin/commit/rollback calls, transaction test traits as production design, cross-module transaction coordination, after-commit behavior, or an outbox. Explicit user authorization is required for the exact mechanism.

Do not rename tables, add prefixes, move models, remove foreign keys, or create database schemas merely to make ownership look cleaner.

## Review Evidence

Before completion, inspect all touched writes and relationship mutations, identify their owner, and confirm the change did not add a second writer or expose a writable model through a new public contract.
