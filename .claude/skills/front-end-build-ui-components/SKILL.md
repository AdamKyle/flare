---
name: front-end-building-ui-components

description: Use this skill when creating, reviewing, or refactoring shared React/TypeScript UI components under resources/js/ui.
---

# Frontend Building UI Components

Use this skill for shared UI primitives under:

resources/js/ui

Shared UI components are generic, reusable building blocks. They must not contain game-specific business logic, API calls, websocket logic, feature state, or feature wording unless passed in through props.

Examples of shared UI components:

* buttons
* progress bars
* loading bars
* alerts
* cards
* tabs
* dropdowns
* inputs
* separators
* tooltips
* containers
* infinite scroll wrappers

## Decide if the component belongs in ui

Place a component in `resources/js/ui` only when it is reusable across unrelated features.

Do not place a component in `resources/js/ui` if it is specific to crafting, inventory, quests, combat, maps, gems, shops, or another game domain.

Feature-specific components belong inside that feature folder.

Generic visual components belong in `resources/js/ui`.

## Required folder layout

Every shared UI component must follow the existing UI folder layout.

Use kebab-case for files and folders.

Use PascalCase for React component names.

Use interfaces for object shapes.

Use `type` only when TypeScript requires it for unions, mapped types, conditional types, tuple-derived values, or `keyof typeof`.

Recommended structure:

resources/js/ui/<component-family>
|
+-- <component-name>.tsx
|
+-- types
|   |
|   +-- <component-name>-props.ts
|
+-- enums
|   |
|   +-- <component-name>-variant.ts
|
+-- styles
|
+-- <component-name>-base-styles.ts
+-- <component-name>-variant-styles.ts

Only create folders that are needed.

## Component rules

UI components must be presentational.

UI components must receive data, labels, callbacks, variants, and state through props.

UI components must not call backend APIs.

UI components must not read game context.

UI components must not read Redux state.

UI components must not subscribe to websockets.

UI components must not contain feature-specific wording.

UI components must not hard-code game-specific labels.

UI components must not hide feature behavior.

## Props rules

Do not define props inline.

Props must live in:

resources/js/ui/<component-family>/types/<component-name>-props.ts

The interface must be named:

<ComponentName>Props

Props must include all labels needed for accessibility.

Do not rely on visual text alone when a screen-reader label is needed.

## Accessibility rules

Every shared UI component must be 100% accessible and screen-reader friendly.

Required:

* use semantic HTML first;
* buttons must be real `button` elements unless there is a real link destination;
* links must be real anchors when navigating;
* form controls must have labels or valid accessible names;
* loading components must expose loading state to assistive technology;
* progress components must use valid progress semantics;
* decorative icons must use `aria-hidden="true"`;
* interactive icons must have an accessible label;
* disabled states must use the correct disabled/aria-disabled behavior;
* visible focus styles must not be removed;
* color must not be the only way to communicate state;
* text contrast must work in light and dark mode.

Progress UI requirements:

* determinate progress must expose `role="progressbar"`;
* determinate progress must expose `aria-valuemin`, `aria-valuemax`, and `aria-valuenow`;
* progress labels must be connected with `aria-labelledby` or `aria-label`;
* loading bars without a known value must expose `role="status"` or `aria-live="polite"` when useful;
* screen-reader-only text must be used when visual text is not enough.

## Mobile-first rules

Every shared UI component must be mobile first and desktop second.

Base classes are mobile styles.

Use responsive prefixes only when enhancing larger screens.

Prefer:

* base mobile layout first;
* `sm:`
* `md:`
* `lg:`
* `xl:`
* `2xl:`
* `3xl:`

Do not design desktop first and then patch mobile.

## Light and dark mode rules

Every shared UI component must support light and dark mode.

Any visible text, border, background, focus, loading, progress, success, warning, danger, and disabled state must have matching `dark:` classes.

Do not add a light-only component.

Do not add a dark-only component.

## Styling rules

Use Tailwind classes from the project theme.

Use `clsx` when classes are conditional.

Put reusable class groups in `styles`.

Use variant enums when the component has multiple visual styles.

Do not inline large class conditionals in the component body.

Do not use raw CSS files for a new UI component unless the existing UI family already requires it.

Do not use arbitrary Tailwind values unless there is no project token that fits and the reason is clear.

Do not use `calc()`.

Do not use `overflow-hidden` unless an existing component family already uses it for a required visual behavior and there is no accessible alternative.

## UI component order

Use this order inside UI components:

1. imports
2. derived IDs or generated IDs
3. derived labels
4. derived class names
5. render helpers
6. final return
7. export default

Keep UI components small.

Extract styles when class strings become hard to read.

## Completion checklist

Before finishing a UI component, verify:

* it belongs in `resources/js/ui`;
* props are in `types`;
* variants are in `enums` when needed;
* reusable class builders are in `styles`;
* it is mobile first;
* it supports light and dark mode;
* it is keyboard accessible;
* it is screen-reader friendly;
* it has valid ARIA only where needed;
* it does not contain feature-specific logic;
* it does not call APIs;
* it does not read feature context;
* it does not use `calc()`;
* it does not use unnecessary `overflow-hidden`.
