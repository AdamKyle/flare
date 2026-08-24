---
name: back-end-conventions
description: Use this skill when writing, reviewing, or refactoring PHP/Laravel app code in this repository.
---

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
* Keep changes scoped to the requested behavior and directly related cleanup.
* Do not rewrite unrelated code.
* Existing violations in touched code are not precedent. Apply `repository-code-quality-and-clean-as-you-go` and bring the touched area into compliance.
* Do not rename or move files unless the behavior/structure being changed requires it.
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
* Do not add `declare(strict_types=1);`; remove it from a touched file.
* Never make classes `final`; remove `final` from a touched class.
* Never use manual scalar cast operators such as `(int)`, `(float)`, `(bool)`, `(string)`, `(array)`, or `(object)` without explicit user permission. Trace and fix the real type contract instead.
* Eloquent `casts()` definitions remain valid and should be inspected before using model values. Do not manually re-cast values that the model/request/DTO already types.
* Use constructor property promotion with `private readonly` for injected dependencies when appropriate.
* Prefer small, focused methods with one clear responsibility.
* Keep parameter lists short.
* Avoid deeply nested conditionals.
* Structure PHP methods around guard clauses and early returns. Invalid, unavailable, failed, skipped, or no-op paths should exit as soon as their outcome is known so the successful path remains linear.
* Do not add `elseif` ladders. When several branches represent guard/failure paths, use separate early-return `if` statements. When one closed value selects one result, prefer an enum-backed `match`. When branches represent distinct workflows, use the owning handler/orchestrator/polymorphic structure instead of growing a conditional ladder.
* A simple `if`/`else` is acceptable only when both mutually exclusive branches are genuinely required and an early return would not make the method clearer.
* Never use nested ternary expressions.
* Prefer readable, explicit code over dense one-liners or clever abstractions.
* Always use braces for `if`, `foreach`, `for`, `while`, and similar control structures.
* Do not use one-line `if` statements.
* Keep imports explicit.
* Do not use leading backslash fully qualified class names in code or docblocks.
* Remove unused imports when editing a file unless project tooling intentionally leaves them.
* Keep methods small and focused; avoid deeply nested conditionals by returning early instead of nesting another branch.
* Add a blank line between a setup/assignment block and the control-flow statement that follows it (`if`, `foreach`, `try`, `return`, etc.). Do not put an assignment line immediately followed by control flow with no blank line between them.
* Add a blank line after a completed control-flow block before the next independent statement/block. Logical blocks must be visually separated; do not jam assignments, conditions, loops, returns, and subsequent work together.
* Use a single space before the opening brace/parenthesis of `if`, `foreach`, `try`, and other control-flow keywords, matching existing formatting in the file.
* Prefer `public` and `private` method/property visibility.
* Do not add `protected` methods or properties unless there is a narrow, documented reason (for example, a random-number-generator extension point the project already relies on for test seams). Note the reason in a short comment or PR description when it is used.

## No Debug Or Direct Output

Production PHP and PHPUnit code must never use temporary or direct debug/output statements.

Do not add or preserve in touched code:

* `fwrite()` to STDOUT/STDERR;
* `echo`;
* `print`;
* `print_r()`;
* `var_dump()`;
* `dump()`;
* `dd()`;
* `ray()`;
* test-only `DEBUG_*` environment branches;
* temporary timer/status diagnostics written to process output.

Use assertions, exceptions, structured application logging that is part of real product behavior, PHPUnit diagnostic flags, and proper response/view mechanisms instead. Debug output is never an acceptable implementation or test seam.

## Domain Types And Control Flow

Finite domain concepts must be represented by an existing or new enum in the owning domain. Do not scatter raw string collections to model statuses, modes, types, outcomes, end reasons, dispositions, or other closed sets.

Do not add code such as:

* `in_array($status, ['failed', 'skipped', ...], true)` for a closed domain set;
* loops over literal status/type strings to discover what an action/result represents;
* dynamically constructed discriminator keys such as `$status.'_item'` or `$status.'_count'`;
* repeated arrays of magic strings representing the same domain concept;
* giant one-line conditions containing a closed list of literal domain values.

Prefer:

* backed enums;
* typed enum parameters/properties;
* `tryFrom()`/validated enum inputs at boundaries;
* small semantic enum methods for classification when the classification belongs to the enum;
* an enum-backed `match` for a short value mapping;
* handlers/orchestrators when each enum case represents a distinct workflow.

Do not infer an object's/action's type by probing which keys happen to exist in an associative array. The type/status should be explicit.

When structured domain data crosses multiple methods/classes or has status-dependent fields, prefer a small typed value/result object with explicit properties over an associative-array pseudo-object. If legacy/API compatibility requires an old array shape, translate at the boundary rather than making internal code continue to infer meaning from dynamic keys.

## ResponseBuilder Service Results

Flare's existing operation-result convention is `App\Game\Core\Traits\ResponseBuilder`.

When a service method represents an application operation that can succeed/fail and its result is consumed directly by an API controller, use `ResponseBuilder` when that matches the surrounding module pattern instead of inventing a new response envelope.

The service owns the operation outcome and returns `successResult(...)` or `errorResult(...)` with the standard `status` field.

The controller stays thin:

* call the service;
* read `$result['status']`;
* remove `status` from the payload;
* return `response()->json($result, $status)`.

Do not duplicate service/business response construction inside the controller. Do not force query/list methods, transformers, or pure domain calculations through `ResponseBuilder` when they do not represent an API operation result.

## Comments And Docblocks

* Do not add inline comments that narrate obvious code.
* Do not add narrative comments in method bodies.
* Keep inline comments only when they explain a non-obvious domain constraint or integration requirement that cannot be made clear through naming and structure.
* Every PHP application class method in a touched file must have a proper method docblock. Follow `back-end-method-documentation` exactly.
* Constructors are the exception to descriptive method documentation: constructor docblocks contain only `@param` tags for their parameters and no summary/description text.
* Non-constructor method docblocks must describe the method's responsibility and document parameters/return values as required by `back-end-method-documentation`.
* Remove stale or incorrect docblocks in touched code and replace them with accurate documentation.
* Do not add class-level narrative docblocks unless the class itself requires non-obvious contract documentation.

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

Controllers, requests, services, values, events, tests, factories, imports, and commands for a feature live in the module they belong to (see `app/Game/Skills` for a reference layout). Do not split a module's own controllers/services/tests across unrelated top-level folders.

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
* When a touched service coordinates distinct workflow phases and also implements every phase, extract the touched phase and its related rules into a focused module collaborator; keep orchestration visible.
* Follow `code-structure-and-size` for large touched classes and methods.

## Dependency Injection

* Do not use `resolve()`, `app()`, or container lookups inside production classes, services, handlers, jobs, commands, value objects, or domain code.
* Dependencies must be injected through the constructor using constructor property promotion.
* Constructor dependencies must use `private readonly ClassName $className` whenever possible.
* Concrete classes with resolvable concrete dependencies use Laravel's zero-configuration container resolution; do not add service-provider bindings solely because a concrete dependency was added.
* If a class/interface is already manually bound, or the dependency requires an interface binding, contextual binding, lifecycle choice, primitive/config value, or other explicit container configuration, update the owning module provider or use an appropriate Laravel attribute only when that is the clearer established pattern.
* Do not configure the same binding redundantly in both a provider and an attribute.
* Controllers must use constructor injection.
* A final touched-path audit must find zero `resolve()` or `app()` service lookups in application classes. Framework-owned boundary declarations are exceptions only when constructor injection is factually unavailable; document the exact boundary.
* Do not hide service location inside Blade directives, generated PHP strings, traits, static helpers, callbacks, events, or value objects.

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

* Fail early when required domain state is missing.
* Prefer explicit null checks when null is a valid possible state.
* Do not hide invalid state behind broad catches.
* Application services do not intentionally throw or rethrow exceptions as part of their public contract. Follow `back-end-service-boundaries-and-failures`.
* Expected service failures return the project's normal `ResponseBuilder` result or the service's established typed result.
* Unexpected failures at a service boundary are logged/reported through the existing project error infrastructure, converted into the service's safe failure outcome, and returned; do not catch an exception, mutate state, and then rethrow it from the service.
* Jobs/commands may let the framework own an exception only when the exception has not already been handled by a service and the framework retry/failure behavior is the intended contract.
* Return Laravel JSON responses consistently from API controllers.
* Keep validation messages and rules in Form Requests when applicable.
* Do not repeat scalar-type validation downstream after a value has already crossed a validated/typed boundary. Follow `back-end-service-boundaries-and-failures`.
* Backend logs and player-facing error messages must be specific about what happened, not generic strings like `Failed`.
* Any unexpected failure in a background or long-running workflow must be logged with full context (identifying ids, current state/progress, exception class, message, and stack trace where available) and must feed the existing monitored bug-report system so it is surfaced immediately.
* When a server exception is found, fix the root cause; logging/reporting is a safety net, not a substitute for the fix.
* Do not leave raw SQL/database exception details as a player-facing message. Admin logs and bug reports get the raw exception; the player gets plain, direct language describing what happened.

## Diagnosing UI Bugs

* When a browser bug or screenshot points to a specific UI element, inspect the actual component that renders that element, not a similarly named component.
* For dropdown/menu bugs specifically, first determine whether the element is React Select, a HeadlessUI `Menu`/`Listbox`, a native `<select>`, or bespoke custom code, before making a fix. Fixing the wrong dropdown implementation leaves the reported bug unfixed.
* Do not say a behavior is "already correct" or "mostly implemented" until you have traced both the screenshot/report path and the actual component/code path and confirmed they match. State fully implemented or unresolved with the exact reason — never "mostly."
* Any shared/reusable component fix must preserve all existing callers' behavior unless the task explicitly scopes a breaking change for one caller.

## Reusing Existing Components, Services, And Helpers

* If the user says an existing component/service/helper/modal exists, search for it and use it.
* If it cannot be found, stop and report the exact missing component/path. Do not create a new substitute component, a simplified stand-in, or a one-off replacement.
* Do not claim "no existing component supports this" unless the report lists the exact files searched and why each existing component cannot be safely adapted.
* The existing component may be adapted only if all existing usages continue working unchanged.
* Any shared component change must preserve all existing callers.
* If a component needs a new mode/prop to support a new use case, add the smallest safe prop and prove existing behavior is unchanged for every current caller.

## Refactoring Rules

* Make the smallest coherent change that solves the requested problem and brings the touched area into skill compliance.
* Preserve public APIs unless the requested behavior requires an intentional change.
* Preserve existing behavior unless explicitly asked to change it.
* Existing skill violations in touched code must be cleaned up; they are not protected legacy patterns.
* Do not expand that cleanup into unrelated modules or repository-wide rewriting.
* When touching old untyped code, type the changed path where the real contract can be proven.
* Do not mass-format unrelated code.
* Do not change unrelated whitespace.
* Do not introduce new packages unless explicitly requested.
* Run `back-end-laravel-simplification` after backend changes.

## Long-Running Process And Player-Facing Payload Conventions

* When adding player-facing panels/statuses, the backend payload must include the exact fields the frontend needs to render them (e.g. max level alongside current level, the specific relevant subset of data rather than everything). Do not force the frontend to infer or recompute backend state from partial data.
* Long-running process hard stops (batch jobs, automations, and similar) must use specific, named enum reasons for why the process stopped, not generic strings like `"failed"` used for every case.
* Player-facing hard stop reasons must be explicit and actionable: state what happened and what the player can do about it, not just that something stopped.
* Chart/graph data payloads must not mix currencies or other distinct units into one generic series. Carry exact, separately named fields for each real currency/unit (for example separate spent/gained fields per currency) rather than one netted or generic pair.
* Action outcome charts must count one action row as one outcome; do not double-count or aggregate multiple action rows into a single chart point.
* Do not expose internal work-unit counts as player-facing item progress when the player requested a count of final items. Track work units internally if needed, but the main player-facing progress must reflect completed/requested final items.
* Keep-highest/keep-best disposition behavior must be documented in code (via clear method/variable naming or a short comment where non-obvious) and must match what the UI copy tells the player will happen.
* When adding a new enum value (for example a new end/stop reason), update all formatters, status message mappings, and tests affected by that value so the new value is handled everywhere the enum is switched over, not just in the one path that motivated the change.
* Start-gate validation for long-running actions (batch jobs, automations, and similar) must come from backend preview/start validation, not frontend heuristics.
* Frontend must not invent authoritative cost, capacity, currency, INT, or set-validity rules; it may only render what the backend preview/status payload provides.
* Manual start blockers must be returned as structured backend data (code, message, blocking, optional links) and enforced again on start, not just shown in preview.
* Runtime must still hard-stop with a specific reason if player state (gold, gold dust, shards, INT, set/bag space, target-set validity) changes after preview/start.
* INT blockers for Craft and Enchant must never fall back to a lower-INT enchant. Resolve the exact intended affix first, then check INT against that resolved affix.
* If the intended enchant requires too much INT, stop before calling the enchant service; do not attempt the enchant and then fail normally.
* Do not let normal server messages spam when a hard blocker (such as INT too low) is already known before attempting the action.
* Currency blockers must name the real currency used by the code for that feature (Gold, Gold Dust, Shards); do not say Gold for a feature that spends Gold Dust or Shards. Do not say Gold for Alchemy unless the code actually uses Gold for that path.
* Public guide/help links pointing at another page must inspect that target page/component and use its existing filter query params. If the filter path cannot be found, stop and report the exact missing path/param instead of inventing a new one.
* Craft Set requires an empty normal unequipped set.
* Craft and Enchant Set may use an empty normal unequipped set or a valid full normal unequipped 23-item set.
* Equipped sets are never valid target sets for Craft Set or Craft and Enchant Set.
* Unique, Mythic, and Cosmic items are never touched (never enchanted, overwritten, or destroyed) by batch enchanting.

## Frontend Conventions (TSX)

These conventions apply to frontend TSX/React changes in this project's game client (`resources/js/game/**`), used until a dedicated frontend-conventions skill exists.

* Reuse existing components, colors, layouts, and modal components. Do not build a new one-off modal when an existing modal already covers the same need.
* Every `dl` must have direct `dt`/`dd` children — do not wrap them in extra `div`s, and do not break a two-column layout by spanning only one side of a label/value pair.
* Long action histories/logs must be collapsible, with the collapsed summary stating how many entries exist.
* Skill bars must show both current and max level whenever max level is available in the payload.
* Chart labels must name the actual currency or unit involved (e.g. "Gold Dust Spent"), never a generic "Currency" label.
* Links that open in a new tab must use `target="_blank"` and `rel="noopener noreferrer"`.
* Panels must use shared status/tone styling components instead of hardcoding one-off status colors per panel.

## PHP Attributes

* Follow `back-end-php-attributes` whenever PHP attributes are added, consumed, reviewed, or changed.
* An attribute must have a known framework or application consumer; do not add decorative/dead metadata.
* Keep custom attribute classes small and typed; keep behavior in the consumer/domain services.
* Do not scatter reflection across application code.

## Output Rules

* Show full file code when asked for full code.
* Do not provide git diffs.
* Do not claim commands were run unless they were actually run.
* If a command cannot be run, say exactly why.
* Keep explanations focused on the code.

## Backend and frontend responsibility boundary

Game/API backends return domain facts, identifiers, enum values, authoritative validation, costs, counts, limits, status, and player-facing messages when the message itself is part of the game behavior.

Do not build frontend control metadata in backend services or transformers when the frontend can map a closed domain value itself. Prohibited examples include dropdown `{value, label}` lists, humanized enum labels, `*_label` presentation fields, button text, screen names, icon names, CSS classes, or presentation-only state.

Frontend code owns presentation labels for closed enum values. Backend enum `label()` methods must not be added solely to support game UI controls.

Admin/reporting/chart endpoints may include explicit presentation labels only when that endpoint contract genuinely owns human-readable report output.

## Laravel enum validation

When a FormRequest validates a PHP backed enum, use Laravel's enum validation rule supported by this repository, such as `Rule::enum(EnumClass::class)`, instead of rebuilding enum values with `Enum::cases()`, `array_column()`, `array_map()`, or literal arrays.

Do not duplicate a closed enum's legal values inside request validation.

## Module migration ownership

When feature ownership moves from one game module to another, keep ownership coherent. Controllers, requests, services, jobs, events, providers, route files, broadcast channel files, tests, and imports that belong to the migrated feature must end in the owning module required by the task.

Do not leave a feature half-migrated across old and new modules unless the task explicitly defines a temporary split. Do not preserve an old module route/provider/channel solely because moving it is inconvenient.

## Existing domain service stability

A feature coordinator/orchestrator should glue together existing domain services. Do not modify Crafting, Enchanting, Alchemy, Inventory, Battle, or another owning-domain service just to create a convenience method for the new feature.

If the requested behavior can be implemented by the existing public contract, use it as-is. If a required capability is genuinely missing, prove the gap by inspecting the owner, add the smallest domain-owned capability, and add focused tests in that domain. Do not leak feature-specific terminology into a general domain service.
