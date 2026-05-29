---
name: back-end-conventions
description: Use this skill when writing, reviewing, or refactoring PHP/Laravel app code in this repository. Applies to services, handlers, controllers, requests, jobs, commands, events, listeners, models, value objects, enums, and migrations. Do not use this skill for PHPUnit test standards; use the phpunit-testing skill for tests.
---

# Back End Conventions

## Scope

Use this skill for PHP/Laravel app code only.

Do not use this skill for PHPUnit tests. Testing rules belong in the `phpunit-testing` skill.

Before changing code, inspect the existing implementation, nearby files, related models, value objects, enums, services, requests, commands, jobs, events, listeners, and current project patterns.

## Project Baseline

- This is a Laravel app.
- Follow the Laravel way before inventing custom patterns.
- Prefer existing project conventions over generic advice.
- Use PHP 8.4-compatible code.
- Keep changes minimal and scoped to the requested behavior.
- Do not rewrite unrelated code.
- Do not rename or move files unless explicitly required.
- Do not add new architecture unless the current code cannot support the change cleanly.

## PHP Style

- Use fully typed method parameters and return types for new or changed methods.
- Use descriptive class, method, property, and variable names.
- Never use single-letter variables.
- Use camelCase for methods, variables, and properties.
- Use PascalCase for class names, enums, traits, and interfaces.
- Do not add `declare(strict_types=1);`.
- Do not make classes `final`.
- Use constructor property promotion with `private readonly` for injected dependencies when appropriate.
- Prefer small, focused methods with one clear responsibility.
- Keep parameter lists short.
- If a method needs too many parameters, use an existing model, value object, DTO-like object, or array shape already used by the project.
- Avoid deeply nested conditionals.
- Prefer clear early returns.
- Always use braces for `if`, `foreach`, `for`, `while`, and similar control structures.
- Do not use one-line `if` statements.
- Keep imports explicit. Do not use leading backslash fully qualified class names in code or docblocks.
- Remove unused imports when editing a file unless project tooling intentionally leaves them.

## Comments And Docblocks

- Do not add inline comments.
- Do not add narrative comments.
- Do not remove existing comments unless explicitly requested.
- Add docblocks only when they match existing project style or are needed for complex array shapes/generics.
- Do not add class-level docblocks unless the nearby project style requires them.
- Prefer readable code over comments explaining basic logic.

## Laravel Conventions

- Use Form Request classes for HTTP validation when a request already exists or validation is non-trivial.
- Keep controllers thin.
- Put business logic in services, handlers, jobs, actions, or existing domain classes.
- Use route model binding where the project already uses it.
- Use Eloquent relationships instead of manual joins when relationships already exist.
- Use model casts for typed persisted attributes.
- Use accessors/mutators only when they belong on the model and are reused.
- Use Laravel collections when they make the code clearer.
- Do not force collections when a simple loop is clearer.
- Use jobs, events, and listeners when the surrounding feature already uses them.
- Use Laravel facades only when they are already the established project pattern for that concern.
- Use config values for environment-specific behavior.
- Do not hard-code values that already exist in config, enums, constants, or value objects.

## Dependency Injection

- Do not use `resolve()`, `app()`, or container lookups inside production classes, services, handlers, jobs, commands, value objects, or domain code.
- Dependencies must be injected through the constructor using constructor property promotion.
- Constructor dependencies must use `private readonly ClassName $className` whenever possible.
- If the class is manually bound in a module service provider, update that provider when adding constructor dependencies.
- If the class is not manually bound and no relevant binding exists, create/register the binding in the appropriate module service provider.
- Controllers must also use constructor injection, but do not require service-provider binding updates solely for controller dependencies.
- Tests may use `resolve()` when following existing project test patterns.
- Do not replace existing container lookups outside the requested scope unless the task explicitly asks for that cleanup.

## Database And Persistence

- Prefer Eloquent model methods, relationships, scopes, and query builders over raw SQL.
- Do not use database transactions by default.
- Use a transaction only when multiple writes must succeed or fail together and there is a real consistency risk.
- If adding a transaction, keep it as small as possible and make the reason obvious from the code structure.
- Do not wrap reads or single simple writes in transactions.
- Avoid N+1 queries by eager loading relationships when needed.
- Do not eager load unrelated relationships.
- Use `update`, `create`, `firstOrCreate`, `updateOrCreate`, or relationship methods where they fit the existing code.
- Do not invent database columns, relationships, scopes, or casts.
- Inspect migrations, models, factories, and existing queries before touching persistence logic.

## Services, Handlers, And Value Objects

- Keep services and handlers small and focused.
- Preserve existing fluent `setUp(...)->handle()` patterns when working in areas that use them.
- Prefer value objects/enums/constants already present in the codebase over raw strings or magic numbers.
- Return existing project value objects where the current feature already uses them.
- Do not add public setters/getters unless they are needed by the existing pattern.
- Keep private methods focused and named after what they do.
- Avoid large private methods that mix validation, persistence, side effects, and response building.
- Separate decision logic from side effects when it improves readability.

## Error Handling And Validation

- Fail early when required state is missing.
- Prefer explicit null checks when null is a valid possible state.
- Do not hide invalid state behind broad catches.
- Catch exceptions only when the code can handle them meaningfully.
- Preserve existing exception behavior unless the requested change requires otherwise.
- Return Laravel JSON responses consistently from API controllers.
- Keep validation messages and rules in Form Requests when applicable.

## Refactoring Rules

- Make the smallest safe change that solves the requested problem.
- Preserve public APIs unless explicitly asked to change them.
- Preserve existing behavior unless explicitly asked to change it.
- Do not opportunistically rewrite legacy files.
- When touching old untyped code, add types only to the changed method if safe and consistent.
- Do not mass-format unrelated code.
- Do not change unrelated whitespace.
- Do not introduce new packages unless explicitly requested.

## Output Rules

- Show full file code when asked for full code.
- Do not provide git diffs.
- Do not claim commands were run unless they were actually run.
- If a command cannot be run, say exactly why.
- Keep explanations focused on the code.