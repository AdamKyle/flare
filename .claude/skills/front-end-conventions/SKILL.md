---
name: front-end-conventions
description: Use for any Flare frontend code change to apply the global React, TypeScript, Tailwind, mobile-first, accessibility, API, screen-manager, side-peek, and quality rules.
---

# Flare Frontend Conventions

Use this skill for all frontend work in Flare.

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

Use existing package scripts:

```bash
yarn cleanup
yarn lint
yarn type-check
yarn build:dev
yarn build
yarn unused-files-check
```

There is no frontend test script in `package.json`; do not claim frontend tests ran unless a real test command was added or provided.
