---
name: front-end-feature-layout
description: Use this skill when creating, reviewing, or refactoring self-contained React/TypeScript frontend feature directories in this repository.
---

## Self-Contained Feature Directory Layout

Use this layout for new self-contained feature sections such as a card, panel, side-peek body, screen section, workflow section, crafting section, shop subsection, or any feature that owns its own API calls, hooks, websocket subscriptions, child components, enums, utils, and local types.

Do not place all files flat in one folder.

Do not define props, state, hook params, hook returns, API requests, or API responses inline inside component or hook files.

Use kebab-case for every file and folder.

Use PascalCase for component names.

Use `interface` for object shapes. Only use `type` when TypeScript requires it for unions, mapped types, conditional types, tuple-derived props, or `keyof typeof` patterns.

Recommended layout:

resources/js/game/components/<parent-feature>/<self-contained-feature>
|
+-- <self-contained-feature>.tsx
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
|       +-- <use-feature-api-hook>.ts
|       |
|       +-- definitions
|           |
|           +-- <use-feature-api-hook>-definition.ts
|           +-- <use-feature-api-hook>-params.ts
|
+-- components
|   |
|   +-- <feature-child-component>.tsx
|   +-- <another-feature-child-component>.tsx
|   |
|   +-- types
|       |
|       +-- <feature-child-component>-props.ts
|       +-- <another-feature-child-component>-props.ts
|
+-- hooks
|   |
|   +-- <use-feature-state>.ts
|   +-- <use-feature-behavior>.ts
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
|   +-- <another-feature-screen>.tsx
|   |
|   +-- types
|       |
|       +-- <feature-screen>-props.ts
|       +-- <base-section-props>.ts
|
+-- enums
|   |
|   +-- <feature>-types.ts
|   +-- <feature>-steps.ts
|
+-- types
|   |
|   +-- <shared-feature-props>.ts
|   +-- <shared-feature-state>.ts
|   +-- <shared-feature-option>.ts
|
+-- utils
|   |
|   +-- <feature>-options.ts
|   +-- <feature>-formatter.ts
|   +-- <feature>-normalizer.ts
|
+-- styles
|   |
|   +-- <feature>-styles.ts
|
+-- web-sockets
|
+-- enums
|   |
|   +-- web-socket-channels.ts
|   +-- web-socket-event-names.ts
|
+-- event-data-definitions
|   |
|   +-- <feature-event-definition>.ts
|
+-- hooks
|
+-- <use-feature-web-socket>.ts
|
+-- definitions
|
+-- <use-feature-web-socket>-params.ts
+-- <use-feature-web-socket>-definition.ts

Placement rules:

- Root feature file:
    - owns the feature shell only;
    - wires feature-level hooks/components together;
    - does not contain API request logic;
    - does not contain websocket subscription logic;
    - does not define inline interfaces.

- `components`:
    - contains renderable child UI specific to this feature;
    - each component props interface lives in `components/types/<component-name>-props.ts`;
    - split components when JSX becomes large or when render helpers become too many.

- `screens`:
    - use only when the feature has internal screens/steps/views;
    - screen props live in `screens/types/<screen-name>-props.ts`;
    - shared screen props live in `screens/types/base-section-props.ts`.

- `api/definitions`:
    - contains API request, response, and entity interfaces;
    - keep backend-shaped fields as snake_case when mirroring server payloads;
    - do not rename API fields to camelCase unless the feature already transforms them.

- `api/hooks`:
    - contains API request hooks only;
    - hook params and return interfaces live in `api/hooks/definitions`;
    - hooks must use `useApiHandler()`;
    - components must not call axios directly.

- `hooks`:
    - contains local feature behavior/state hooks;
    - hook params, return interfaces, and state interfaces live in `hooks/definitions`;
    - do not hide JSX-heavy rendering in hooks.

- `types`:
    - contains shared feature interfaces used by multiple folders;
    - do not use this for one component’s props when `components/types` or `screens/types` is more specific.

- `utils`:
    - contains pure functions only;
    - no React state;
    - no JSX;
    - no API calls.

- `styles`:
    - contains class maps or class builder functions only when JSX classes become reusable or conditional enough to justify extraction;
    - use `clsx` for conditional class names.

- `web-sockets`:
    - contains feature-specific websocket channels, event names, event payload interfaces, and websocket hooks;
    - websocket hook params live in `web-sockets/hooks/definitions`;
    - use existing websocket provider/hooks;
    - do not initialize Echo manually.

Interface naming rules:

- Component props:
    - `<ComponentName>Props`
    - file: `<component-name>-props.ts`

- Screen props:
    - `<ScreenName>Props`
    - file: `<screen-name>-props.ts`

- Hook params:
    - `<UseHookName>Params`
    - file: `<use-hook-name>-params.ts`

- Hook return:
    - `<UseHookName>Definition`
    - file: `<use-hook-name>-definition.ts`

- Hook state:
    - `<UseHookName>State`
    - file: `<use-hook-name>-state.ts`

- API response:
    - `<Entity>ApiResponseDefinition`
    - file: `<entity>-api-response-definition.ts`

- API request:
    - `<Action>RequestDefinition`
    - file: `<action>-request-definition.ts`

Rendering rules inside self-contained features:

- Do not leave large inline conditional JSX.
- Do not use inline ternary JSX for major loading/default branches.
- Do not use inline `{condition && (...)}` for multi-line blocks.
- Extract descriptive render helpers.
- Render helpers must be named by what they render.
- Render helpers must use early returns.

Preferred pattern:

- `renderCraftTypeFieldset`
- `renderArmourTypeFieldset`
- `renderSearchFieldset`
- `renderCraftingSummary`
- `renderCraftItems`
- `renderCraftForNpcCheckbox`
- `renderCraftForEventCheckbox`
- `renderMessages`
- `renderSelectedItem`
- `renderTimeoutMessage`
- `renderInventoryFullMessage`
- `renderActions`

Derived values and spacing:

- Group related derived values together.
- Leave a blank line between logical const groups.
- Do not create dense blocks of unrelated derived const declarations.
- Keep derived values above handlers only when handlers depend on them.
- Keep handlers above render helpers.
- Keep render helpers above the final return.

Preferred component order:

1. context hooks
2. custom hooks
3. state
4. refs
5. derived values
6. data-fetching helpers
7. effects
8. getters
9. handlers
10. render helpers
11. final return
12. export default

Do not add a new folder just because this structure lists it. Add only the folders the feature actually needs.