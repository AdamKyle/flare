---
name: front-end-conventions
description: Use for any Flare frontend code change to apply the global React, TypeScript, Tailwind, mobile-first, accessibility, API, screen-manager, side-peek, and quality rules.
---

# Flare Frontend Conventions

Use this skill for all frontend work in Flare. Also apply `repository-code-quality-and-clean-as-you-go`.

## Source of truth

Frontend source lives in:

```text
resources/js
```

Styles live in:

```text
resources/css
```

The main styling source is:

```text
resources/css/tailwind.css
```

Do not use `frontend/src`. That belongs to other projects, not Flare.

## Non-negotiable frontend rules

- Use TypeScript and React functional components.
- Use Tailwind classes from the project theme.
- Build mobile first; desktop enhances the base mobile layout.
- Every UI change must support light and dark mode.
- Every interactive UI must be keyboard accessible and screen-reader friendly.
- Components do not call Axios directly.
- Components do not hard-code API URLs.
- API hooks use `useApiHandler()` and `getUrl()`.
- Backend/API payload fields stay snake_case.
- Props live in local `types` files.
- Hook contracts live in `definitions` files when substantial.
- Shared UI under `resources/js/ui` must stay generic and presentational.
- Game-domain UI belongs under `resources/js/game`.
- Admin UI belongs under `resources/js/admin`.
- Full game screens use the screen manager.
- Right-side overlays use the side-peek registry/emitter system.
- Echo/Reverb subscriptions use the websocket provider/hooks.
- Global character/monster/announcement state belongs in `GameDataProvider` only when truly global.
- Do not set state or start listeners during render.
- Existing frontend violations in touched code must be cleaned up; do not copy them as precedent.
- All React hooks must be called unconditionally before any component return.
- Never disable an ESLint/TypeScript rule to bypass a design or type problem without explicit permission.
- Cross-root imports must use the repository's configured aliases when an alias exists; do not reach an aliased root with deep `../../..` paths.
- Never use `console.log`, `console.debug`, `console.info`, `console.warn`, `console.error`, `console.trace`, `console.table`, or similar console debugging/output in application code. Handle errors through the existing API/error/UI path. Remove existing console output when touching that path.

## Canonical import aliases

The Vite source aliases are:

```text
configuration
event-system
api-handler
game-data
game-utils
components
ui
service-container
service-container-provider
screen-manager
```

Use these aliases when importing across those source roots. Relative imports are for files inside the same local feature/component area.

If Vite, TypeScript, or ESLint alias configuration is inconsistent for an alias needed by the touched code, align the configurations instead of working around the mismatch with a deep relative import.

## Frontend ownership map

```text
resources/js/ui                         shared generic UI primitives
resources/js/game/reusable-components   reusable game-domain components
resources/js/game/components            game feature UI
resources/js/game/components/side-peeks game side-peek content/registration
resources/js/game/screen-bindings       screen-manager bindings
resources/js/configuration/screen-manager app screen constants, props, registry
resources/js/screen-manager             generic screen manager engine
resources/js/api-handler                shared API handler and API context
resources/js/websocket-handler          Echo/Reverb websocket infrastructure
resources/js/game-data                  global game data provider and wires
resources/js/event-system               event emitter system
resources/js/admin/<feature>            admin React features
resources/js/utils                      shared utilities
```

## Component rules

- Keep components readable and small.
- Use render helpers for large conditional JSX.
- Avoid nested ternary JSX.
- Hooks must be called before early returns.
- Handlers should be named by action: `handleClose`, `handleNextClick`, `handleOpenSidePeek`.
- Use derived booleans for repeated conditions.
- Do not hide API payload building, validation, or websocket logic inside visual components.

## Styling rules

Use the Flare palettes from `resources/css/tailwind.css`, including:

```text
primary, brand, danube, gray, rose, emerald, indigo, mango-tango,
marigold, wisp-pink, regent-st-blue, artifact-colors, cosmic-colors,
item-skill-training, glacier
```

Use existing item/chat utility classes from:

```text
resources/css/item-colors.css
resources/css/chat-colors.css
```

Do not add raw hex colors in JSX.

## Accessibility baseline

- Buttons must be real buttons.
- Links must be real links when navigating.
- Icon-only buttons need `aria-label`.
- Decorative icons need `aria-hidden="true"`.
- Form controls need labels or valid accessible names.
- Loading/progress states need screen-reader semantics.
- Dialogs/side-peeks need dialog semantics and Escape/focus behavior.
- Color must not be the only state indicator.

## Validation commands

After any code change, the repository-wide mandatory gates from `repository-code-quality-and-clean-as-you-go` apply:

```bash
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```

For frontend implementation work, also run `yarn build:dev` unless the task explicitly forbids builds.

All ESLint warnings/errors in changed code must be resolved. There is no frontend test script in `package.json`; do not claim frontend tests ran unless a real test command was added or explicitly provided.
