---
name: front-end-build-ui-components
description: Use when creating, reviewing, or refactoring shared Flare UI primitives under resources/js/ui.
---

# Flare Shared UI Component Building

Use this skill for components under:

```text
resources/js/ui
```

Shared UI components must be generic, reusable, accessible, mobile first, and free of game/admin business logic.

## Belongs in `ui`

Good candidates:

- buttons;
- alerts;
- cards;
- inputs;
- dropdown shells;
- form wizard shell;
- progress/loading bars;
- tabs;
- tooltips;
- generic side-peek shell;
- infinite scroll wrappers;
- containers and separators.

Does not belong in `ui`:

- crafting logic;
- inventory item behavior;
- quest/admin business rules;
- map/teleport/traverse behavior;
- character/monster domain display that knows game data;
- API calls;
- websocket subscriptions.

## Required structure

Use the existing pattern:

```text
resources/js/ui/<family>
├── <component>.tsx
├── types
│   └── <component>-props.ts
├── enums
│   └── <component>-variant.ts
└── styles
    ├── base-style.ts
    └── variant-style.ts
```

Only create needed folders.

## Props and variants

Props live in `types`.

Variants live in `enums`.

Class maps live in `styles` when reusable or conditional.

Use `clsx` for conditional class names.

## Accessibility requirements

Shared UI must provide or accept all labels required for accessibility.

- Buttons use `<button type="button">` unless intentionally submitting.
- Icon-only buttons require `aria-label`.
- Decorative icons require `aria-hidden="true"`.
- Inputs require labels or accessible names.
- Alerts should announce blocking errors where appropriate.
- Progress bars expose progress semantics.
- Loading indicators expose status semantics.
- Dialog shells expose dialog semantics and focus/Escape behavior.

## Mobile and dark mode

All shared UI must work on mobile first.

Base classes are mobile. Breakpoint classes enhance larger screens.

All visible colors must have light and dark mode coverage.

Do not ship a light-only or dark-only shared component.

## Shared UI checklist

A shared UI change is acceptable when:

- it is truly generic;
- it has typed props;
- variants/styles are extracted when useful;
- no game/admin/API/websocket logic leaked in;
- it is keyboard accessible;
- it is screen-reader friendly;
- it uses Tailwind project tokens;
- it supports mobile, desktop, light mode, and dark mode.
