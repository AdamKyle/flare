---
name: front-end-components-and-rendering
description: Use when writing or refactoring Flare React components, render helpers, conditional JSX, component order, props, handlers, derived state, and readable component flow.
---

# Flare Components and Rendering Rules

Use this skill when creating, reviewing, or refactoring React components in `resources/js`.

## Component style

Flare components are functional React components written in TypeScript.

Prefer explicit, readable component flow over dense JSX.

Use this order inside substantial components:

1. imports;
2. props destructuring or `props` access;
3. context hooks;
4. custom hooks;
5. local state;
6. refs;
7. derived values;
8. handlers;
9. effects where appropriate;
10. render helpers;
11. final return;
12. export default.

Small components can stay simple, but do not let large components become unstructured.

## Props rules

Do not define props inline for feature-level or shared components.

Props live in local `types` folders:

```text
components/types/<component>-props.ts
screens/types/<screen>-props.ts
ui/<family>/types/<component>-props.ts
```

Use interface for object shapes:

```ts
export default interface ButtonProps {
  label: string;
  on_click: () => void;
}
```

Use type only when TypeScript requires or benefits from it:

- unions;
- mapped types;
- conditional types;
- tuple-derived types;
- `keyof typeof` mappings;
- function aliases where appropriate.

## Naming rules

Use existing Flare callback naming where it already exists:

```text
on_click
on_close
is_open
aria_label
footer_primary_action
footer_secondary_action
```

Do not mass-convert existing snake_case props to camelCase.

Backend/API shaped fields remain snake_case.

Local React-only variables may use camelCase when not mirroring existing component/API style.

Use descriptive names. Avoid:

```text
n
r
d
e
val
idx
obj
temp
```

Prefer:

```text
parsedAmount
selectedItem
fieldErrors
validationResult
currentIndex
nextPage
```

## Conditional JSX rules

Large conditional JSX must be moved into render helpers.

Avoid multi-line inline blocks like:

```tsx
{condition && (
  <div>...</div>
)}
```

Avoid nested ternary JSX.

This is acceptable only for tiny text/value choices:

```tsx
{is_active ? 'Active' : 'Inactive'}
```

For anything larger, use a helper:

```tsx
const renderSelectedItem = () => {
  if (!selectedItem) {
    return null;
  }

  return <SelectedItemDetails selectedItem={selectedItem} />;
};
```

## Render helper rules

Render helpers must:

- be named for what they render;
- use early returns;
- stay close to the final return;
- avoid side effects;
- avoid calling hooks;
- return `null` when nothing should render.

Good names:

```text
renderHeader
renderFooter
renderFormError
renderCraftItems
renderSelectedItem
renderTimeoutMessage
renderInventoryFullMessage
renderLoadingState
renderEmptyState
renderActions
```

Bad names:

```text
renderStuff
renderThing
renderConditional
renderData
```

## Hooks rule

React hooks must be called unconditionally at the top level of the component or inside custom hooks.

Do not put hooks after early returns.

Bad:

```tsx
if (loading) {
  return <Loader />;
}

const value = useMemo(...);
```

Good:

```tsx
const value = useMemo(...);

if (loading) {
  return <Loader />;
}
```

## Early return rules

Early returns are good when they simplify the component.

Use early returns for full-component states:

- blocked provider context;
- missing required data;
- full loader state;
- hard error state;
- permission denied state.

Do not hide complex state branches in nested JSX.

## Derived values

Use clear derived booleans and values:

```tsx
const hasSelectedItem = selectedItem !== null;
const shouldShowFooter = componentProps.has_footer;
const isLastStep = current_index === computed_total_steps - 1;
```

Do not repeat the same condition throughout JSX.

Group related derived values with blank lines between unrelated groups.

## Handler rules

Handlers should be named by user intent or event result:

```text
handleClose
handleClear
handleNextClick
handleOpenTraverseSidePeek
handleSecondaryActionClick
```

Handlers may call API hook actions, emit side-peeks, update local state, or call props callbacks.

Handlers should not contain large API request object construction unless it is tiny and unique.

## Accessibility in components

Every interactive component must have a programmatic accessible name.

Rules:

- buttons are real `<button type="button">` unless submitting a form;
- links are real `<a>` elements when navigating to an href;
- icon-only buttons require `aria-label`;
- decorative icons use `aria-hidden="true"`;
- form controls have labels or correctly connected accessible names;
- modal/dialog content uses correct dialog semantics;
- loading and error states are announced where useful;
- focus behavior is preserved.

## Motion and transitions

Flare uses `framer-motion` for screen transitions and some animated flows.

Rules:

- keep motion purposeful;
- do not animate layout in a way that breaks keyboard or screen-reader navigation;
- set `aria-hidden` for hidden inactive animated panels;
- disable pointer events for hidden overlays/screens;
- do not trap focus inside a hidden motion element.

## Component checklist

A component is acceptable when:

- props are typed in a local type file;
- hooks are called before returns;
- large conditional JSX is in render helpers;
- handlers are named clearly;
- derived values are readable;
- loading/error/empty states are explicit;
- interactive controls are accessible;
- mobile and dark mode classes are present where needed.
