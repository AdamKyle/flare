---
name: front-end-component-creation
description: Use when creating a new Flare frontend React component, deciding placement, props, extraction, rendering style, accessibility behavior, and styling structure.
---

# Flare Frontend Component Creation

Use this skill whenever creating a new React component in Flare.

## First decision: where does it belong?

Place the component by ownership:

```text
resources/js/ui                         generic shared UI primitive
resources/js/game/reusable-components   reusable game-domain component
resources/js/game/components/<feature>  game feature component
resources/js/admin/<feature>            admin feature component
resources/js/configuration/...          app configuration/registry only
```

Do not put game-specific or admin-specific components into `ui`.

## File structure

Use kebab-case files and PascalCase component names.

For a feature component:

```text
components/<component-name>.tsx
components/types/<component-name>-props.ts
```

For a shared UI component:

```text
ui/<family>/<component-name>.tsx
ui/<family>/types/<component-name>-props.ts
ui/<family>/enums/<component-name>-variant.ts
ui/<family>/styles/base-style.ts
ui/<family>/styles/variant-style.ts
```

Only create folders that are needed.

## Props

Do not define props inline for shared or substantial components.

Use interface:

```ts
export default interface ComponentNameProps {
  label: string;
  on_click: () => void;
}
```

Follow existing prop naming where present: `on_click`, `on_close`, `is_open`, `aria_label`.

Keep API-shaped data fields snake_case.

## Component body order

Use this order for substantial components:

1. context hooks;
2. custom hooks;
3. state;
4. refs;
5. derived values;
6. handlers;
7. effects;
8. render helpers;
9. final return.

Hooks must never be called conditionally or after early returns.

## Rendering

Large conditional JSX belongs in render helpers.

Good:

```tsx
const renderFooter = () => {
  if (!hasFooter) {
    return null;
  }

  return <Footer />;
};
```

Avoid nested ternaries and large inline `{condition && (...)}` blocks.

## Accessibility

Every new component must be accessible:

- real buttons for actions;
- real links for navigation;
- labels for form fields;
- `aria-label` for icon-only buttons;
- `aria-hidden="true"` for decorative icons;
- visible focus styles;
- screen-reader text or live regions for important status changes;
- no color-only state indicators.

## Styling

Use Tailwind theme tokens, mobile-first classes, and dark mode classes.

Use `clsx` for conditional classes.

Move repeated or conditional class maps to a local `styles` file.

Do not add raw hex colors or static inline styles for normal layout/color.

## Completion checklist

A new component is acceptable when:

- it lives under the correct owner folder;
- props are in a type file;
- API/websocket/game-data logic is not mixed into presentational components;
- rendering is readable;
- mobile-first and dark mode styles exist;
- controls are keyboard and screen-reader accessible;
- imports use aliases where crossing major folders.
