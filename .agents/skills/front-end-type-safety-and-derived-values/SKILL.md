---
name: front-end-type-safety-and-derived-values
description: Use when changing TypeScript definitions, props, API contracts, hook contracts, event payloads, mapped types, enums, null handling, derived values, and type assertions in Flare frontend code.
---

# Flare Type Safety and Derived Values

Use this skill when adding or changing TypeScript types, interfaces, props, API definitions, hook contracts, screen-manager maps, side-peek maps, websocket payloads, enums, derived values, and type assertions.

## Core rule

Flare frontend runs with strict TypeScript and `noImplicitAny`.

Do not use `any`.

Avoid unsafe assertions.

Keep contracts explicit and local to the owning feature.

## Interface vs type

Use `interface` for object shapes:

```ts
export default interface GuideQuestDefinition {
  id: number;
  name: string;
}
```

Use `type` for:

- unions;
- mapped types;
- conditional types;
- tuple types;
- `keyof typeof` mappings;
- utility-composed aliases;
- event payload unions.

Examples where `type` is correct:

```ts
export type AppScreenName = keyof AppScreenPropsMap;

export type SidePeekEventPayload = {
  [K in keyof SidePeekComponentPropsMap]: [K, SidePeekComponentPropsMap[K]];
}[keyof SidePeekComponentPropsMap];
```

## No `any`

Do not use `any` in new frontend code.

Prefer:

```text
unknown
Record<string, unknown>
generic type parameters
specific interfaces
mapped types
```

If a third-party boundary forces unknown data, parse or narrow it before use.

## Type assertions

Avoid type assertions when TypeScript can infer the type.

Be careful with:

```ts
as unknown as Something
```

Only use this pattern at framework boundaries where the project already needs it, such as generic component registries, and keep it isolated.

Do not use assertions to hide real contract mismatches.

## API definitions

API definitions live under the feature API folder:

```text
api/definitions
```

Rules:

- backend-shaped fields stay snake_case;
- request definitions represent what the backend expects;
- response definitions represent what the backend returns;
- entity definitions represent reusable API objects;
- components do not define API contracts inline.

Do not mix local UI-only fields into API response definitions unless the backend really sends them.

Use separate local UI state interfaces when needed.

## Props definitions

Component props live in `types` folders.

Naming:

```text
<ComponentName>Props
<ScreenName>Props
<FeatureName>Props
```

Files:

```text
<component-name>-props.ts
<screen-name>-props.ts
```

Use optional props only when the component truly supports absence.

Do not make everything optional to silence TypeScript.

## Hook definitions

Feature hooks should have explicit return definitions when reused or substantial:

```text
hooks/definitions/use-<hook>-definition.ts
hooks/definitions/use-<hook>-params.ts
hooks/definitions/use-<hook>-state.ts
```

API hook definitions live in:

```text
api/hooks/definitions
```

Naming:

```text
UseCraftItemApiDefinition
UseCraftItemApiParams
UseManageFormSectionDefinition
```

## Enum rules

Use enums or `as const` maps for shared named values.

Current examples:

```text
ButtonVariant
AlertVariant
CraftingApiUrls
Screens
SidePeekComponentRegistrationEnum
ChannelType
```

Rules:

- use enums/constants for repeated string values;
- do not scatter magic strings;
- use `as const` maps when you need literal keys and values like screen names;
- keep enum files in local `enums` folders.

## Derived values

Use derived variables to make conditions readable.

Good:

```ts
const isLastStep = current_index === computed_total_steps - 1;
const hasSelectedItem = selectedItem !== null;
const shouldRenderFooter = componentProps.has_footer;
const canLoadNextPage = canLoadMore && !isLoadingMore;
```

Bad:

```tsx
{current_index === computed_total_steps - 1 && componentProps.has_footer && ...}
```

Group derived values by purpose.

Avoid huge blocks of unrelated constants.

## Null and undefined handling

Be explicit about nullable data.

Use `null` for intentionally absent state where existing code does so.

Use early returns when required data is missing.

Do not blindly use non-null assertions.

Bad:

```ts
selectedItem!.id
```

Good:

```ts
if (!selectedItem) {
  return;
}

const selectedItemId = selectedItem.id;
```

## Number parsing

Frontend input values are strings.

Do not accidentally turn empty string into zero.

Use clear parsing utilities or validation functions.

Use `parseInt(value, 10)` when parsing integers.

Check `Number.isNaN(parsedValue)`.

## Registry map typing

Screen manager and side-peek registry maps are intentionally typed.

When adding a screen:

- add a screen constant;
- add props to the props map;
- add component to the registry;
- bind it through screen bindings.

When adding a side-peek:

- add enum key;
- add props map entry;
- add registry entry;
- emit with typed props.

Do not bypass registry typing with loose records unless you are inside the existing registry bridge.

## Type checklist

A TypeScript change is acceptable when:

- there is no new `any`;
- object shapes use interfaces;
- unions/mapped types use `type`;
- API contracts stay faithful to backend payloads;
- props/hook definitions live in local definition files;
- optional fields are truly optional;
- null handling is explicit;
- assertions are rare and justified;
- derived values make component logic easier to read.
