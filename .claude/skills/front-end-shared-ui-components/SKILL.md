---
name: front-end-shared-ui-components
description: Use when creating or changing generic shared UI primitives under resources/js/ui, including buttons, inputs, alerts, cards, progress, tabs, tooltips, loaders, and side-peek shell components.
---

# Flare Shared UI Components

Use this skill for shared UI primitives under:

```text
resources/js/ui
```

Shared UI components are generic building blocks. They must not contain game-specific business logic, admin-only business logic, API calls, websocket logic, feature state, or feature-specific wording unless passed through props.

## What belongs in `ui`

Good shared UI candidates:

- buttons;
- alerts;
- cards;
- containers;
- dropdown shells;
- inputs;
- file upload controls;
- form wizard shell;
- progress bars;
- loading bars;
- separators;
- tabs;
- tooltips;
- generic side-peek shell;
- generic infinite scroll wrappers;
- generic description-list components.

Do not put these in `ui`:

- crafting-specific components;
- inventory-specific item logic;
- quest-specific forms;
- monster stat rendering;
- map teleport/traverse behavior;
- character sheet domain logic;
- guide quest admin business logic;
- websocket subscriptions;
- API request hooks.

## Shared UI folder layout

Use the existing UI component family layout:

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

Only create folders that are needed.

## Component purity

Shared UI components should be presentational.

They receive through props:

- labels;
- accessible labels;
- values;
- options;
- loading flags;
- disabled flags;
- callbacks;
- visual variants;
- children;
- extra class names where the existing component supports them.

They must not:

- call API hooks;
- call `useGameData`;
- call `useWebsocket`;
- emit game side-peek events;
- read meta tags;
- contain hard-coded game copy;
- know Laravel endpoint strings;
- initialize services.

## Styling shared UI

Use Tailwind classes from the project theme.

Use `clsx` for conditional classes.

Use enum-driven variants when a component has multiple visual states.

Keep reusable style groups in `styles` files when classes become long or conditional.

Examples already in the project:

```text
ui/buttons/styles/button/base-styles.ts
ui/buttons/styles/button/variant-styles.ts
ui/alerts/styles/base-style.ts
ui/alerts/styles/variant-style.ts
ui/loading-bar/styles/base-progress-bar-style.ts
ui/loading-bar/styles/progress-height-variant-style.ts
```

## Button rules

Buttons must:

- render as real `<button>` elements for actions;
- include `type="button"` unless intentionally submitting a form;
- be disabled with the real `disabled` attribute when unavailable;
- have an accessible label from visible text or `aria_label`;
- preserve focus styles;
- not rely on color alone for state.

Icon-only buttons need explicit labels:

```tsx
<button type="button" aria-label="Close panel">
  <i className="fas fa-times" aria-hidden="true" />
</button>
```

Decorative icons need `aria-hidden="true"`.

## Input rules

Inputs must:

- have a visible label or valid accessible name;
- expose disabled state correctly;
- show validation errors in a way screen readers can access;
- connect errors with `aria-describedby` when possible;
- use clear placeholder text only as a hint, not as the only label;
- preserve visible focus rings;
- support light and dark mode.

A generic input should accept labels/error props when the feature needs validation display. Do not make every feature hand-roll input error markup.

## Alerts and errors

Use `Alert` for user-facing messages and `ApiErrorAlert` for API errors.

Error alerts should be announced to assistive tech when relevant:

- use `role="alert"` for blocking validation/API errors;
- use `aria-live="polite"` for non-blocking status updates;
- include specific text, not just color or icons.

Closable alerts need accessible close buttons.

## Progress and loading components

Determinate progress bars must expose:

```text
role="progressbar"
aria-valuemin
aria-valuemax
aria-valuenow
```

Indeterminate loaders should use:

```text
role="status"
aria-live="polite"
```

or an equivalent accessible pattern.

Do not communicate progress only with color or animation.

Clamp computed progress values between 0 and 100.

Inline style for computed progress width is acceptable when the width is dynamic.

## Side-peek shell

The generic side-peek shell lives in:

```text
resources/js/ui/side-peek
```

It owns generic dialog/shell behavior only.

Game-specific side-peek content and registration lives under:

```text
resources/js/game/components/side-peeks
```

The side-peek shell must:

- use `role="dialog"`;
- use `aria-modal="true"`;
- have a titled dialog via `aria-labelledby` or `aria-label`;
- support Escape close;
- support close button keyboard interaction;
- prevent background scroll when open;
- return focus or manage focus predictably;
- preserve mobile full-width behavior.

## Tooltips

Tooltips must not hide required information from keyboard or touch users.

Use tooltip content for supplemental explanation, not required labels or validation errors.

Interactive tooltip triggers must be keyboard reachable.

## Shared UI checklist

A shared UI component is acceptable when:

- it is truly generic;
- props live in `types`;
- variants live in `enums` when needed;
- reusable class groups live in `styles` when helpful;
- it has no API/game/admin business logic;
- it is mobile first;
- it supports light and dark mode;
- it is accessible and screen-reader friendly;
- it does not introduce raw colors or arbitrary layout hacks.
