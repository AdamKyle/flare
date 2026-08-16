---
name: back-end-laravel-simplification
description: Use after writing or changing Laravel/PHP production code to simplify the touched area for clarity, consistency, and maintainability without changing behavior.
---

# Laravel/PHP Simplification

## Goal

Refine touched PHP/Laravel code so it is easier to read, test, debug, and extend while preserving exact behavior.

Apply repository rules before generic framework style.

## Preserve behavior

Do not change public behavior merely to make code shorter.

Preserve:

- return values and response shapes;
- status codes;
- side effects;
- persistence semantics;
- event and queue behavior;
- authorization and validation;
- observable ordering where ordering is part of the behavior.

## Prefer explicit code

Choose clear, explicit code over clever or compressed code.

Improve touched code by:

- reducing unnecessary nesting;
- using early returns where they clarify control flow;
- removing redundant conditions and duplicate work;
- using clear domain names;
- consolidating logic that represents the same responsibility;
- preserving useful abstractions and removing abstractions that add no meaning;
- removing obvious narrative comments while keeping comments that explain a non-obvious constraint.

Never use nested ternary expressions. Do not simplify code into `elseif` ladders. Prefer guard-clause early returns for failure/non-applicable paths, enum-backed `match` for short closed-value mappings, and handlers/orchestrators when branches are separate workflows.

Do not replace readable code with dense chained expressions solely to reduce lines.

## Laravel conventions

Use Laravel's existing framework behavior before introducing custom infrastructure.

Concrete classes with resolvable concrete dependencies normally use zero-configuration container resolution. Do not add service-provider bindings solely because a concrete class has constructor dependencies.

Bind interfaces, contextual dependencies, lifecycle-specific services, or other cases that actually require container configuration using the project's module pattern.

Prefer existing Eloquent relationships/scopes/query builders, Form Requests, enums, value objects, events, jobs, and module services when they already own the behavior.

Do not create a custom abstraction that duplicates a Laravel feature already used by the repository.

## Scope

Simplify the code changed by the task and directly related code needed to make that path compliant.

Do not run a repository-wide style rewrite.

Existing violations encountered in the touched area are not protected. Apply the clean-as-you-go skill while preserving behavior.

## Simplify Data Shapes, Not Just Syntax

Do not keep a weak associative-array shape and then add loops/conditions to reverse-engineer what it means.

When touched code discovers status/type by checking dynamic keys, repeated literal arrays, or key-name prefixes/suffixes, fix the data shape at the owning boundary where behavior can be preserved:

- represent the closed status/type with an enum;
- carry the status/type explicitly;
- use a typed value/result object when the structure crosses collaborators;
- keep legacy serialization/adaptation at the external compatibility boundary only.

Reducing a 20-line branch to one dense `in_array()` or `foreach` is not simplification when the resulting code hides the domain model.

## Small Footprint

Prefer fewer responsibilities and less duplicated logic, not merely fewer files.

Do not create speculative factories, wrappers, DTOs, helpers, traits, services, or interfaces for future work. Add an abstraction only when the current task has at least one concrete responsibility that requires it and the abstraction makes the current path simpler.

When migrating one workflow out of a legacy large class, remove the migrated branch and now-unused helpers/tests in the same change. Do not leave a parallel implementation behind.

## Small-footprint replacement rule

A replacement architecture must reduce accidental complexity, not redistribute it.

Do not replace one do-everything class with another do-everything class under a cleaner namespace. Do not create orchestrators, handlers, factories, DTOs, registries, or interfaces for future workflows before those workflows exist.

Factories resolve. Orchestrators coordinate. Handlers execute one workflow. Existing domain services perform the domain mechanics. Keep each layer at that level of abstraction.
