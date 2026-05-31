---

name: back-end-conventions
description: Use this skill when writing, reviewing, or refactoring PHP/Laravel app code in this repository. Applies to services, handlers, controllers, requests, jobs, commands, events, listeners, models, value objects, enums, migrations, module providers, routes, middleware, and app code under app/Game. Do not use this skill for PHPUnit test standards; use the phpunit-testing skill for tests.
-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

# Back End Conventions

## Scope

Use this skill for PHP/Laravel app code only.

Do not use this skill for PHPUnit tests. Testing rules belong in the `phpunit-testing` skill.

Before changing code, inspect the existing implementation, nearby files, related models, value objects, enums, services, requests, commands, jobs, events, listeners, providers, routes, middleware, migrations, factories, and current project patterns.

## Project Baseline

* This is a Laravel app.
* Follow the Laravel way before inventing custom patterns.
* Prefer existing project conventions over generic advice.
* Use PHP 8.4-compatible code.
* Keep changes minimal and scoped to the requested behavior.
* Do not rewrite unrelated code.
* Do not rename or move files unless explicitly required.
* Do not add new architecture unless the current code cannot support the change cleanly.
* Do not create a new folder, provider, route file, service style, controller style, or request style when the target module already has an existing pattern.

## App Placement Rules

* Avoid adding new app code under `app/Flare` unless it is a new Eloquent model or truly global application code that affects the whole app.
* If shared game code is needed, prefer `app/Game/Core` before `app/Flare` when it belongs to the game domain.
* If code belongs to a specific game area, put it under the active `app/Game/<ModuleName>` module.
* Do not place module-specific controllers, services, jobs, handlers, loggers, requests, providers, or commands in global app folders.
* Eloquent models live in `app/Flare/Models`.

## PHP Style

* Use fully typed method parameters and return types for new or changed methods.
* Use descriptive class, method, property, and variable names.
* Never use single-letter variables.
* Use camelCase for methods, variables, and properties.
* Use PascalCase for class names, enums, traits, and interfaces.
* Do not add `declare(strict_types=1);`.
* Do not make classes `final`.
* Use constructor property promotion with `private readonly` for injected dependencies when appropriate.
* Prefer small, focused methods with one clear responsibility.
* Keep parameter lists short.
* Avoid deeply nested conditionals.
* Prefer clear early returns.
* Always use braces for `if`, `foreach`, `for`, `while`, and similar control structures.
* Do not use one-line `if` statements.
* Keep imports explicit.
* Do not use leading backslash fully qualified class names in code or docblocks.
* Remove unused imports when editing a file unless project tooling intentionally leaves them.

## Comments And Docblocks

* Do not add inline comments.
* Do not add narrative comments.
* Do not remove existing comments unless explicitly requested.
* Add docblocks only when they match existing project style or are needed for complex array shapes/generics.
* Do not add class-level docblocks unless the nearby project style requires them.
* When adding a controller, request, service, command, job, event, listener, provider, or model method to an existing file, match the docblock style already used in that file.

## App Game Module Structure

Game features are organized as modules under `app/Game/**`.

Follow the existing folder structure in the target module.

Common module folders include:

* `Controllers`
* `Controllers/Api`
* `Requests`
* `Services`
* `Providers`
* `Events`
* `Listeners`
* `Jobs`
* `Handlers`
* `Loggers`
* `Values`
* `Concerns`
* `Traits`
* `Middleware`
* `Console`

Do not invent a new module layout when the target module already has one.

## Controllers

* API controllers live under the target module’s `Controllers/Api` namespace.
* Web controllers live under the target module’s `Controllers` namespace.
* Controllers must follow nearby controller patterns in the same module.
* Use constructor injection for services.
* Do not use `resolve()` or `app()` in controllers.
* Controllers should be thin: accept requests/models, call services, return responses.
* API controller methods should return `JsonResponse` when nearby API controllers do.
* Use response shapes and status codes consistent with nearby controllers.
* Use route model binding where the project already uses it.
* Do not put business logic in controllers.

## Requests

* Form requests live in the target module’s existing request folder.
* Use the existing folder name for that module.
* Do not rename or normalize existing folders.
* Requests extend `Illuminate\Foundation\Http\FormRequest`.
* Use `authorize()`, `rules()`, and `messages()` when needed.
* Do not put business logic in requests.
* Do not create a request class for trivial endpoints unless the surrounding module pattern uses request classes for that type of action.

## Services, Handlers, Loggers, And Value Objects

* Services live under the module’s `Services` folder.
* Handlers live under the module’s `Handlers` folder.
* Loggers live under the module’s `Loggers` folder.
* Value objects live under the module’s `Values` folder.
* Preserve existing fluent `setUp(...)->handle()` patterns when working in areas that use them.
* Prefer value objects, enums, and constants already present in the codebase over raw strings or magic numbers.
* Do not add public setters/getters unless they are needed by the existing pattern.
* Avoid large private methods that mix validation, persistence, side effects, and response building.

## Dependency Injection

* Do not use `resolve()`, `app()`, or container lookups inside production classes, services, handlers, jobs, commands, value objects, or domain code.
* Dependencies must be injected through the constructor using constructor property promotion.
* Constructor dependencies must use `private readonly ClassName $className` whenever possible.
* If the class is manually bound in a module service provider, update that provider when adding constructor dependencies.
* If the class is not manually bound and no relevant binding exists, create/register the binding in the appropriate module service provider.
* Controllers must use constructor injection, but do not require service-provider binding updates solely for controller dependencies.

## Providers

* Each module may have `Providers/ServiceProvider.php`.
* Module service providers register module services, handlers, coordinators, loggers, values, commands, and middleware aliases.
* Providers extend `Illuminate\Support\ServiceProvider as ApplicationServiceProvider`.
* Use `register()` for container bindings.
* Use `boot()` for middleware aliases or boot-time framework setup.
* Use the existing provider binding style in the module.
* If adding a constructor dependency to a manually bound class, update the provider binding in the same change.
* Do not register a service in the wrong module provider.
* Do not create a provider when an existing module provider should be updated.

## Events And Listeners

* Events live in the module’s `Events` folder.
* Listeners live in the module’s `Listeners` folder.
* Event providers live in `Providers/EventsProvider.php` when the module uses one.
* Event providers extend `Illuminate\Foundation\Support\Providers\EventServiceProvider`.
* Register event/listener mappings in the provider’s `$listen` property.
* Broadcast events should follow existing event patterns in nearby modules.
* Use `ShouldBroadcast` or `ShouldBroadcastNow` only when the behavior requires it.
* Use `ShouldBroadcastNow` when the UI must update immediately and the surrounding code expects synchronous broadcast behavior.

## Jobs

* Jobs live in the module’s `Jobs` folder.
* Jobs should follow the constructor and dependency-loading pattern already used in the target module.
* Do not create recursive job dispatch behavior unless the existing feature explicitly works that way.
* Do not add queue behavior, delays, retries, or middleware outside the requested behavior.

## Commands

* After-development repair/cleanup/import-prep commands live under `app/Console/AfterDevelopment`.
* After-development commands must be registered the same way existing AfterDevelopment commands are registered.
* If an AfterDevelopment command must be run by the import flow, call it from `app/Flare/GameImporter/Console/Commands/MassImportCustomData.php` above the `importInformationSection()` call.
* Do not otherwise modify `MassImportCustomData.php` unless explicitly required for registering/calling an AfterDevelopment command.
* Module commands that are not AfterDevelopment commands must live in the owning module’s console/command area, such as `app/Game/<Module>/Console`.
* Module commands must be registered in the owning module’s service provider following that module’s existing command registration pattern.
* If a module command is scheduled, register it in `app/Console/Kernel.php` as a scheduled command.
* Do not place module-specific commands in `app/Console/Commands`.
* Commands should call services where the project pattern supports it.

## Routes

* Game API route files live under `routes/game/**/api.php`.
* Game web route files live under `routes/game/**/web.php`.
* Broadcast channel files live under `routes/game/**/channels.php`.
* Route files are mapped by `RouteServiceProvider`.
* Because route namespaces are mapped, route files commonly use string controller syntax: `'uses' => 'Api\ControllerName@method'`.
* Do not use fully qualified controller arrays unless the route file already uses that style.
* Use the existing middleware grouping style in the target route file.
* If the route accepts a `Character` route parameter or acts on a character, include `is.character.who.they.say.they.are` unless nearby equivalent routes prove a different protection is used.
* If throttling is required, use the exact throttle value requested or the value used by nearby equivalent routes.
* Do not add routes to the wrong module route file.

## Middleware

* Module middleware lives in the module’s `Middleware` folder.
* Middleware aliases are registered in the module provider `boot()` method when that is the module pattern.
* Do not register middleware in random providers.
* Do not bypass existing middleware checks in controllers or services.

## Database And Persistence

* Prefer Eloquent model methods, relationships, scopes, and query builders over raw SQL.
* Do not use database transactions by default.
* Use a transaction only when multiple writes must succeed or fail together and there is a real consistency risk.
* Avoid N+1 queries by eager loading relationships when needed.
* Do not eager load unrelated relationships.
* Do not eager load large JSON/log relationships for normal read endpoints unless the endpoint specifically needs those logs.
* Use `update`, `create`, `firstOrCreate`, `updateOrCreate`, or relationship methods where they fit the existing code.
* Do not invent database columns, relationships, scopes, or casts.
* Inspect migrations, models, factories, and existing queries before touching persistence logic.
* Add indexes for new query paths.
* Use composite indexes when the query filters and sorts by multiple columns.
* Do not add indexes that are not used by the new or changed query path.

## Models And Factories

* Eloquent models live in `app/Flare/Models`.
* Factories live in `database/factories`.

When creating a new database table that has an Eloquent model:

1. Create the model in `app/Flare/Models`.
2. Add `HasFactory` to the model.
3. Create the matching factory in `database/factories`.
4. Keep factory defaults valid, minimal, and project-consistent.
5. Ensure factory defaults create internally consistent records.
6. Do not add unrelated model fields, factory states, relationships, casts, or behavior.

If model setup is needed in tests, the PHPUnit skill owns the test trait and test setup rules.

## Migrations

* Migrations live in `database/migrations`.
* Use Laravel migration classes consistent with existing migrations.
* Do not use defensive `Schema::hasColumn()` or `Schema::hasTable()` guards unless the existing migration pattern for the specific task requires it.
* Use explicit `up()` and `down()` behavior.
* Add foreign keys only when the project already uses them for the related tables or the task explicitly requires them.
* Add indexes for lookup paths introduced by the change.
* Index names should be explicit when needed to avoid length limits.
* Do not modify old migrations unless explicitly requested.

## Error Handling And Validation

* Fail early when required state is missing.
* Prefer explicit null checks when null is a valid possible state.
* Do not hide invalid state behind broad catches.
* Catch exceptions only when the code can handle them meaningfully.
* Preserve existing exception behavior unless the requested change requires otherwise.
* Return Laravel JSON responses consistently from API controllers.
* Keep validation messages and rules in Form Requests when applicable.

## Refactoring Rules

* Make the smallest safe change that solves the requested problem.
* Preserve public APIs unless explicitly asked to change them.
* Preserve existing behavior unless explicitly asked to change it.
* Do not opportunistically rewrite legacy files.
* When touching old untyped code, add types only to the changed method if safe and consistent.
* Do not mass-format unrelated code.
* Do not change unrelated whitespace.
* Do not introduce new packages unless explicitly requested.

## Output Rules

* Show full file code when asked for full code.
* Do not provide git diffs.
* Do not claim commands were run unless they were actually run.
* If a command cannot be run, say exactly why.
* Keep explanations focused on the code.
