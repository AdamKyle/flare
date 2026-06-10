---
name: front-end-component-creation
description: Use this skill when adding a new React/TypeScript component, deciding where it belongs, and creating the required API hooks, definitions, props, state, utils, and supporting files.
---

# Frontend Component Creation

Use this skill whenever adding a new React/TypeScript component.

Before creating files, inspect the nearest existing feature, parent component, API hook, type folder, and UI pattern. Match the existing structure unless it violates the frontend conventions skill.

Do not create a flat component folder when the component owns API calls, local hooks, child components, websocket behavior, enums, utils, or multiple screens.

## Decide where the component goes

Place the component by ownership, not by convenience.

If the component is only used by one feature, place it inside that feature:

resources/js/game/components/<parent-feature>/<feature>/components/<component-name>.tsx

If the component is a main self-contained feature section, create a feature folder:

resources/js/game/components/<parent-feature>/<new-feature>/<new-feature>.tsx

If the component is one screen or step inside a feature flow, place it in:

resources/js/game/components/<parent-feature>/<feature>/screens/<screen-name>.tsx

If the component is reused by multiple nearby components inside the same feature, keep it inside that feature’s `components` folder.

If the component is reused across unrelated features, place it in the appropriate shared/common component area only after confirming an existing shared pattern exists.

Do not move a component into a shared folder just because it might be reused later.

## Required structure for a self-contained feature

Use this layout when the new component has API endpoints, local state hooks, child components, or feature-specific types:

resources/js/game/components/<parent-feature>/<new-feature>
|
+-- <new-feature>.tsx
|
+-- api
|   |
|   +-- definitions
|   |   |
|   |   +-- <entity>-definition.ts
|   |   +-- <entity>-api-response-definition.ts
|   |   +-- <entity>-request-definition.ts
|   |
|   +-- enums
|   |   |
|   |   +-- <feature>-api-urls.ts
|   |
|   +-- hooks
|       |
|       +-- <use-feature-api>.ts
|       |
|       +-- definitions
|           |
|           +-- <use-feature-api>-definition.ts
|           +-- <use-feature-api>-params.ts
|
+-- components
|   |
|   +-- <child-component>.tsx
|   |
|   +-- types
|       |
|       +-- <child-component>-props.ts
|
+-- hooks
|   |
|   +-- <use-feature-state>.ts
|   |
|   +-- definitions
|       |
|       +-- <use-feature-state>-definition.ts
|       +-- <use-feature-state>-params.ts
|       +-- <use-feature-state>-state.ts
|
+-- screens
|   |
|   +-- <feature-screen>.tsx
|   |
|   +-- types
|       |
|       +-- <feature-screen>-props.ts
|
+-- enums
|   |
|   +-- <feature>-steps.ts
|   +-- <feature>-types.ts
|
+-- types
|   |
|   +-- <shared-feature-props.ts>
|   +-- <shared-feature-state.ts>
|
+-- utils
|
+-- <feature>-formatter.ts
+-- <feature>-normalizer.ts
+-- <feature>-options.ts

Only create folders that are actually needed.

## API endpoint rules

If the component calls backend endpoints, create an API hook.

API hooks go in:

api/hooks/<use-feature-api>.ts

API hook params go in:

api/hooks/definitions/<use-feature-api>-params.ts

API hook return shape goes in:

api/hooks/definitions/<use-feature-api>-definition.ts

API URLs go in:

api/enums/<feature>-api-urls.ts

API request interfaces go in:

api/definitions/<action>-request-definition.ts

API response interfaces go in:

api/definitions/<entity>-api-response-definition.ts

Backend-shaped fields must remain snake_case unless an existing mapper already converts them.

Components must not call axios directly.

Components must not build raw URLs inline.

Use the existing `useApiHandler()` pattern.

## Component props and state rules

Do not define props inline in the component file.

Component props go in:

components/types/<component-name>-props.ts

The interface must be named:

<ComponentName>Props

Do not define state interfaces inline in component or hook files.

Hook state goes in:

hooks/definitions/<use-hook-name>-state.ts

Hook params go in:

hooks/definitions/<use-hook-name>-params.ts

Hook return interfaces go in:

hooks/definitions/<use-hook-name>-definition.ts

Use `interface` for object shapes.

Use `type` only when TypeScript requires it for unions, mapped types, tuple-derived values, conditional types, or `keyof typeof`.

## Hook rules

Create a local hook when the component has reusable state behavior, API orchestration, websocket subscription behavior, or enough logic that the component becomes hard to read.

Local feature hooks go in:

hooks/<use-feature-hook>.ts

API hooks go in:

api/hooks/<use-feature-api>.ts

Hooks must not return JSX.

Hooks must not hide rendering behavior.

Hooks may return state, derived values, handlers, and fetch functions.

## Rendering rules

Do not use large inline conditional JSX.

Do not use inline ternary JSX for major loading, empty, error, or default branches.

Do not use multi-line `{condition && (...)}` blocks.

Create descriptive render helpers.

Render helpers must use early returns.

Example names:

- `renderLoadingState`
- `renderEmptyState`
- `renderErrorMessage`
- `renderHeader`
- `renderFilters`
- `renderTable`
- `renderActions`
- `renderFooter`

Default return should be the main UI path.

## Component order

Use this order:

1. context hooks
2. custom hooks
3. Redux hooks
4. state
5. refs
6. memoized values
7. derived values
8. data-fetching helpers
9. effects
10. getters
11. handlers
12. render helpers
13. final return
14. export default

## Spacing and formatting

Group related const declarations.

Add a blank line between logical groups.

Do not create dense blocks of unrelated derived values.

Do not add comments unless required by the existing project pattern or explicitly requested.

Do not add placeholder empty handlers.

Do not add unused files, unused props, unused imports, or unused definitions.

## Final check before completion

Confirm the new component:

- is in the correct ownership folder;
- has props in the correct `types` folder;
- has API definitions in `api/definitions`;
- has API hook params and return interfaces in `api/hooks/definitions`;
- has local hook params, state, and returns in `hooks/definitions`;
- has no inline interfaces;
- has no large inline conditional JSX;
- follows import order;
- only created folders that are actually needed.