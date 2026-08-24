---
name: repository-code-quality-and-clean-as-you-go
description: Use for every implementation, refactor, review, or test change to enforce factual inspection, touched-area cleanup, prohibited patterns, dependency restrictions, and mandatory quality gates.
---

# Repository Code Quality and Clean-As-You-Go


## Skill execution

Before implementation, read every repository `SKILL.md` under the configured skill roots, including nested skill files. Do not skip required skills because of token, context, or output limits.

All applicable skills are cumulative. A nearby code pattern never overrides an explicit skill rule.

If two explicit skill rules genuinely conflict and the repository/task does not resolve the conflict factually, report the exact conflict instead of choosing one by assumption.

## Skill files are immutable during implementation

Repository skills are governing inputs, not application implementation output.

During an application implementation, refactor, bug fix, test-writing task, or code-review remediation task, never create, edit, delete, move, rename, reformat, or auto-fix files under the configured repository skill roots.

If implementation work exposes a missing or unclear rule, keep the skill files unchanged and report the rule gap separately. Do not modify the governing rules to make the current implementation easier to complete or to make existing code appear compliant.

Skill files may be changed only when the user explicitly requests a dedicated skill-maintenance deliverable. Skill maintenance must remain separate from application-code implementation.

## Source of truth

The current repository is the source of truth for architecture, behavior, naming, placement, APIs, models, frontend structure, tests, and conventions.

Reference code may explain an old capability, but reference code does not override the current repository's architecture or rules.

Before changing behavior, inspect the exact current code path. Do not infer a class, relation, cast, route, payload field, hook, component, color, service, or test abstraction from a name alone.

If required context cannot be proven from the repository, stop and report the exact missing context instead of inventing it.

## No guessing

Never:

- invent a class, method, route, field, relationship, enum case, event, payload shape, color, dependency, or configuration value;
- assume a model value needs conversion without inspecting the model and input contract;
- assume a shared component can support a new use case without inspecting its callers;
- claim a command, test, build, lint check, or coverage result ran when it did not;
- copy a nearby pattern merely because it exists if that pattern violates a repository skill.

Inspect first. Change second.

## Clean as you go

Existing violations are technical debt, not precedent.

When a task touches a file, method, component, hook, controller, service, test, or directly related code path that violates an applicable skill, bring the touched area into compliance as part of the task.

Examples include:

- prohibited class modifiers;
- manual scalar casts;
- deep relative imports into an aliased source root;
- invalid hook ordering or render-time side effects;
- oversized methods mixing multiple responsibilities;
- business logic in controllers;
- test helpers, direct model factories, reflection, or invalid mocks;
- duplicate or dead code exposed by the change;
- lint, type, formatting, or unused-file violations in the touched area.

Do not preserve a violation because it predates the task.

Do not expand a local cleanup into an unrelated repository-wide rewrite. The cleanup boundary is the code being changed plus directly related code that must change to make that path compliant and coherent.

## Prohibited PHP patterns

Never declare a class as `final`.

When touching an existing `final` class, remove `final` as part of the touched-area cleanup.

Do not add `declare(strict_types=1);`. Remove it when encountered in a touched file.

Do not use PHP scalar cast operators without explicit user permission:

- `(int)`
- `(float)`
- `(bool)`
- `(string)`
- `(array)`
- `(object)`

This prohibition applies especially to Eloquent model values. Inspect the model's `casts()` definition, accessor/mutator, request validation, DTO/value object, database type, or upstream contract and use the already-defined type instead of re-casting it.

Laravel/Eloquent model casts are not prohibited. They are the model-level typing mechanism that often makes manual scalar casting unnecessary.

If an existing manual cast is encountered in touched code, do not blindly delete it. Trace the source type and replace the cast with the correct typed/validated/model-cast contract so behavior is preserved. If that cannot be proven safely, report the exact blocker instead of guessing.

## No Temporary Diagnostics

Never add temporary debug/output code to production or tests. This includes `fwrite`, `echo`, `print`, `print_r`, `var_dump`, `dump`, `dd`, `ray`, STDOUT/STDERR writes, `DEBUG_*` environment branches, and frontend `console.*` debugging calls.

If such code exists in a touched path, remove it. Do not leave it behind disabled, environment-gated, test-gated, or commented out.

## Closed Domain Values

A repeated finite set of domain strings is a type-definition problem, not a convenience-array problem.

Use an owning-domain enum for closed statuses, modes, types, results, dispositions, end reasons, and similar concepts. Do not duplicate literal arrays, loop through possible string statuses to infer behavior, or encode a discriminator into dynamic associative-array keys.

Keep internal data explicit and typed. Adapt to legacy payload shapes only at compatibility boundaries.

## Dependencies

Do not install, add, remove, upgrade, downgrade, or replace a Composer, Yarn, npm, or other library dependency without explicit user instruction.

Do not work around the restriction by copying package code into the repository.

Before proposing a dependency, inspect the packages already installed and the existing repository abstractions.

## Mandatory quality gates

After code changes are complete, the following repository quality gates must always be run and must succeed:

```bash
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```

All ESLint rules must be respected. Treat warnings in changed code as violations even if the lint process exits with status zero.

Do not suppress a lint or TypeScript rule with `eslint-disable`, `@ts-ignore`, `@ts-expect-error`, or an equivalent bypass unless explicit permission was given for that exact suppression.

If `yarn cleanup` changes frontend files, run `yarn lint` and `yarn type-check` again against the final state.

Run additional task-specific tests, coverage, or builds required by the applicable skills and task.

A task is not complete while a required quality gate is failing. Fix the cause; do not document a known failure as success.

## Mandatory Prohibited-Pattern Audit

Before completing backend/test work, search the entire touched path and directly affected tests. Every match must be removed or identified as a factual, explicitly permitted framework boundary:

- reflection or another non-public visibility bypass in tests;
- non-lifecycle helper methods declared on test classes;
- `assertTrue(true)`/`assertFalse(false)` or equivalent assertion padding;
- direct model factories in `*Test.php` files;
- manual queued-job execution;
- provider/container/framework-plumbing tests;
- prohibited raw-GD/map fixture construction;
- production `resolve()`/`app()` service location;
- new coverage-ignore annotations;
- manual scalar casts, `final` classes, `strict_types`, debug output, and undocumented protected members.

Test passage and coverage percentages do not prove this audit passed. Record the commands and any explicitly permitted matches when proof of work is requested.

If a required command cannot run because dependencies are unavailable, state that exact factual blocker. Do not install dependencies without permission.

## Autonomous repository safety

During autonomous implementation, refactor, test-writing, or review-remediation work, do not run any `git` command unless the user explicitly asks for a git operation in that task. Read the repository with ordinary filesystem tools such as `rg`, `grep`, `find`, `sed`, `cat`, and `diff` instead.

Do not create or modify editor, IDE, assistant, or workspace metadata unless the task explicitly targets that metadata. Examples include `.idea/**`, `.vscode/**`, generated task-lock files, assistant state files, and unrelated repository-local tool metadata.

Do not create planning READMEs, scratch files, temporary scripts, debug files, generated reports, or other repository artifacts unless the task explicitly requires them. `proof_of_work.md` is allowed only when the task requires it.

## Migration and replacement discipline

When a task replaces a legacy feature or responsibility, replacement means removal, not duplication or namespace movement.

Do not move a giant legacy service, processor, controller, hook, or test class into a new module and call that a refactor. Extract only the behavior that still belongs, express it through the new architecture, then delete the superseded implementation and its implementation-detail tests when the task scope says the legacy path is replaced.

Do not keep compatibility adapters, fallback branches, duplicated implementations, legacy result serializers, or transitional code after the task's target architecture no longer needs them.

Do not add convenience methods to an unrelated existing domain service merely to make a new feature easier to implement. Reuse the service's current public contract. Extend the owning domain only when a concrete missing domain capability is proven, belongs there, and is required by the task. Keep that addition minimal and test it in the owning domain.
