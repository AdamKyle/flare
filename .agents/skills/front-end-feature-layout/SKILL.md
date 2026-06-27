---
name: front-end-feature-layout
description: Use when creating or refactoring Flare frontend feature folders, game feature components, admin features, hooks, definitions, utils, styles, and local file organization.
---

# Flare Frontend Feature Layout

Use this skill when creating a new feature, restructuring a feature, reviewing folder placement, or deciding where components, hooks, API files, definitions, utilities, styles, and types belong.

## Feature ownership rule

Put code where the owning domain lives.

Do not move a feature into `ui` just because it renders UI.

`ui` is for generic primitives. Game and admin features belong in their domain folders.

## Game feature layout

For a game feature under `resources/js/game/components/<feature>`, prefer this structure when the folders apply:

```text
resources/js/game/components/<feature>
├── <feature>.tsx
├── components
│   ├── <child-component>.tsx
│   └── types
│       └── <child-component>-props.ts
├── screens
│   ├── <feature-screen>.tsx
│   └── types
│       └── <feature-screen>-props.ts
├── hooks
│   ├── use-<feature>.ts
│   └── definitions
│       ├── use-<feature>-definition.ts
│       ├── use-<feature>-params.ts
│       └── use-<feature>-state.ts
├── api
│   ├── definitions
│   │   ├── <entity>-definition.ts
│   │   ├── <action>-request-definition.ts
│   │   └── <action>-response-definition.ts
│   ├── enums
│   │   └── <feature>-api-urls.ts
│   └── hooks
│       ├── use-<action>-api.ts
│       └── definitions
│           ├── use-<action>-api-definition.ts
│           └── use-<action>-api-params.ts
├── enums
│   └── <feature>-type.ts
├── types
│   └── <shared-feature-type>.ts
├── utils
│   ├── <feature>-formatter.ts
│   └── <feature>-normalizer.ts
└── styles
    └── <feature>-styles.ts
```

Only create folders that are needed.

Do not add empty folders.

## Admin feature layout

Admin features live under:

```text
resources/js/admin/<feature>
```

Use this structure when applicable:

```text
resources/js/admin/<feature>
├── <feature>.tsx
├── manage-<feature>.tsx
├── manage-<feature>-base.tsx
├── api
│   ├── definitions
│   ├── enums
│   └── hooks
│       └── definitions
├── components
│   └── types
├── form-components
│   └── types
├── hooks
│   └── definitions
├── definitions
├── types
└── utils
```

Current admin guide quest code follows this pattern.

Use it as the reference for future admin forms.

## Shared UI layout

Shared UI primitives live under:

```text
resources/js/ui/<component-family>
```

Typical structure:

```text
resources/js/ui/<component-family>
├── <component>.tsx
├── types
│   └── <component>-props.ts
├── enums
│   └── <component>-variant.ts
└── styles
    ├── base-style.ts
    └── variant-style.ts
```

Examples:

- `ui/buttons`
- `ui/alerts`
- `ui/cards`
- `ui/input`
- `ui/form-wizard`
- `ui/loading-bar`
- `ui/progress`
- `ui/side-peek`
- `ui/tabs`
- `ui/tool-tips`

## Reusable game-domain components

Use:

```text
resources/js/game/reusable-components
```

for components that are reusable inside the game but not generic enough for `ui`.

Examples include components that know about game terms, game item display, game stats, character data, game currencies, map concepts, monster concepts, or specific game styling semantics.

Do not put game-domain components into `ui`.

## File naming rules

Use kebab-case file names:

```text
craft-item-list.tsx
craft-item-list-props.ts
use-craft-item-api.ts
use-craft-item-api-definition.ts
crafting-api-urls.ts
```

Use PascalCase React component names:

```tsx
const CraftItemList = (...) => { ... };
```

Use camelCase for local variables and functions unless matching existing snake_case props/API fields.

Keep backend/API payload fields as snake_case.

## Type location rules

Use the narrowest type location:

- component props: `components/types/<component>-props.ts`;
- screen props: `screens/types/<screen>-props.ts`;
- hook params/returns/state: `hooks/definitions/*`;
- API request/response/entity definitions: `api/definitions/*`;
- API hook params/returns: `api/hooks/definitions/*`;
- shared feature types used by multiple folders: `types/*`;
- enum values: `enums/*`.

Do not define component props inline.

Do not define API request/response shapes inside components.

Do not define hook return types inline when the hook is feature-level or shared.

## Utility rules

Feature utilities live in the feature `utils` folder.

Cross-feature utilities live in:

```text
resources/js/utils
resources/js/utils/hooks
```

Utilities must be pure unless clearly named as hooks.

Utility files must not contain JSX.

Utility files must not call APIs.

Utility files must not subscribe to websockets.

Utility files must not mutate React state.

## Styles folder rules

Move class maps and class builder functions into `styles` when classes become conditional or repeated.

Use `clsx` for conditional Tailwind class names.

Do not create a `styles` folder for a single simple class string.

Feature styles stay in the feature folder. Shared UI styles stay in the shared UI folder.

## Root feature file rules

The root feature component should wire the feature together.

It may:

- call feature hooks;
- call API hooks;
- pass data to child components;
- render top-level loading/error/empty/success states;
- choose screens/sections.

It should not:

- contain raw API URLs;
- build large request payloads inline;
- contain many unrelated child components inline;
- define props interfaces inline;
- contain websocket subscription details;
- contain long validation functions;
- become a dumping ground for all JSX.

## Component extraction rule

Extract a child component when:

- JSX becomes hard to scan;
- a block has its own props/behavior;
- the same UI pattern is repeated;
- a render helper becomes large;
- a form section has a clear domain name;
- a section needs independent accessibility labels or focus behavior.

Do not extract tiny one-line markup just to create files.

## Feature completion checklist

A feature layout is acceptable when:

- files live under the owning domain folder;
- generic UI is not mixed with feature business logic;
- API definitions are not inside components;
- props live in local type files;
- hooks have definitions when they expose reusable contracts;
- utilities are pure;
- styles are extracted only when helpful;
- folder names follow existing Flare conventions.
