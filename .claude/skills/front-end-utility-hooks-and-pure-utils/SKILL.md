---
name: front-end-utility-hooks-and-pure-utils
description: Use when adding or changing Flare custom hooks, shared hooks, feature hooks, pure utilities, formatters, normalizers, and state helpers.
---

# Flare Utility Hooks and Pure Utilities

Use this skill when creating or changing custom hooks, feature hooks, shared hooks, pure utilities, formatters, normalizers, and state helpers.

## Hook vs utility

Use a hook when the code needs React behavior:

- state;
- effects;
- refs;
- memoization tied to React;
- context access;
- event subscriptions;
- API hook composition;
- websocket hook composition.

Use a pure utility when the code is deterministic and independent of React:

- formatting;
- normalization;
- validation without state;
- request mapping;
- percentage math;
- option construction;
- payload transformations.

Do not put React hooks in utility files.

Do not put JSX in utility files.

## Shared hook location

Cross-feature hooks live in:

```text
resources/js/utils/hooks
```

Feature-local hooks live in the owning feature:

```text
<feature>/hooks
<feature>/hooks/definitions
```

API hooks live in:

```text
<feature>/api/hooks
<feature>/api/hooks/definitions
```

Websocket feature hooks can live under feature `web-sockets/hooks` if the feature has a websocket submodule, or under the existing global game-data/websocket owner if global.

## Hook definition files

Substantial hooks must expose definitions:

```text
hooks/definitions/use-<hook>-definition.ts
hooks/definitions/use-<hook>-params.ts
hooks/definitions/use-<hook>-state.ts
```

Use explicit names:

```ts
export default interface UseManageFormSectionDefinition {
  ...
}
```

Do not inline hook return object types for shared/substantial hooks.

## Hook behavior rules

Hooks may:

- own state;
- expose named actions;
- call other hooks;
- subscribe/unsubscribe in effects;
- return render wire components only when following existing websocket wire patterns.

Hooks must not:

- return large chunks of JSX for normal UI rendering;
- hide feature layout inside hook internals;
- call hooks conditionally;
- perform side effects during render;
- swallow errors silently;
- mutate objects directly.

## Pure utility rules

Pure utilities must:

- accept explicit inputs;
- return explicit outputs;
- avoid hidden dependencies;
- avoid reading DOM/meta tags;
- avoid React state;
- avoid API calls;
- avoid date/time randomness unless explicitly part of the utility;
- be easy to test manually/reason about.

Good utility examples:

```text
normalize-crafting-type.ts
guide-quest-form-data-util.ts
fetch-health-bar-percentage.ts
xp-bar-percentage.ts
get-url.ts
shallow-equal.ts
```

## Naming

Hooks start with `use`:

```text
use-craft-item-api.ts
use-manage-form-section-data.ts
use-open-location-info-side-peek.ts
use-websocket.ts
```

Utilities use action/object names:

```text
normalize-crafting-type.ts
fetch-health-bar-percentage.ts
guide-quest-form-data-util.ts
calculate-clam-centre-offset.ts
```

Keep names specific.

## Effect rules

Effects must:

- have clear dependencies;
- clean up subscriptions/listeners;
- avoid state updates after unmount;
- not replace simple derived values that could be computed in render;
- not be used to mirror props into state without a reason.

Avoid disabling exhaustive-deps unless the existing pattern genuinely requires it and the reason is clear.

## Hook/utility checklist

A hook or utility change is acceptable when:

- React-dependent code is in hooks;
- pure logic is in utilities;
- definitions exist for substantial hook contracts;
- effects have cleanup where needed;
- no side effects happen during render;
- no JSX is hidden in ordinary utility files;
- errors are exposed or handled intentionally;
- names clearly describe behavior.
