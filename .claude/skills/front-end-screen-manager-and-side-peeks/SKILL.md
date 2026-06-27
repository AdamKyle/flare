---
name: front-end-screen-manager-and-side-peeks
description: Use when adding or changing Flare full game screens, screen bindings, screen-manager props/registry, side-peek overlays, side-peek emitters, side-peek registry, and overlay accessibility.
---

# Flare Screen Manager and Side-Peeks

Use this skill when adding, changing, or reviewing full game screens, screen bindings, screen-manager registry entries, side-peek overlays, side-peek emitters, side-peek component registrations, or overlay accessibility.

## Core distinction

Use the screen manager for full game screens.

Use side-peeks for right-side overlays/panels.

Do not add full-screen views directly to `GameSection`.

Do not use side-peeks for flows that should replace the main game screen.

## Screen manager files

Screen manager app configuration lives under:

```text
resources/js/configuration/screen-manager
```

Important files:

```text
screen-manager-constants.ts
screen-manager-props.ts
screen-manager-registry.ts
screen-manager-kit.tsx
screen-intent.ts
```

Generic screen manager engine lives under:

```text
resources/js/screen-manager
```

Game bindings live under:

```text
resources/js/game/screen-bindings
```

## Adding a full game screen

When adding a new full game screen, complete all steps:

1. Create the screen component under the owning game feature folder.
2. Create the props interface in the feature `types` folder.
3. Add the screen key to `screen-manager-constants.ts`.
4. Add the props mapping to `screen-manager-props.ts`.
5. Add the component mapping to `screen-manager-registry.ts`.
6. Create a binding component under `resources/js/game/screen-bindings/<feature>-bindings` when screen visibility is event/hook driven.
7. Export/register the binding in `resources/js/game/screen-bindings/index.ts` or the local binding collection.
8. Verify close/back behavior calls `pop`, `replaceWith`, or `resetTo` correctly.

Do not skip the props map or registry. The screen must be typed end-to-end.

## Screen binding rules

Bindings connect feature visibility state to screen-manager navigation.

Use:

```ts
useBindScreen({
  when,
  to: Screens.SOME_SCREEN,
  props: (): ScreenPropsOf<typeof Screens.SOME_SCREEN> => ({ ... }),
  mode: 'push',
  dedupeKey: 'some-screen',
});
```

Rules:

- `when` should be a clear boolean;
- `to` must match the intended screen constant;
- props must match `ScreenPropsOf<typeof Screens.X>`;
- close callbacks should pop only when this binding owns the active screen;
- use a correct `dedupeKey`; do not copy/paste unrelated keys such as `shop`;
- binding components render `null`.

## Screen accessibility

Full screens that replace or overlay game content must:

- have a clear heading;
- expose a close/back control when appropriate;
- mark hidden animated screens as `aria-hidden`;
- prevent pointer interaction with hidden screens;
- preserve keyboard navigation;
- avoid trapping focus in hidden panels.

`GameSection` already hides the base `GameCard` with `aria-hidden` and pointer-event changes when stack depth is greater than zero. Preserve this behavior.

## Side-peek files

Generic shell:

```text
resources/js/ui/side-peek
```

Game side-peek system:

```text
resources/js/game/components/side-peeks/base
```

Important files:

```text
base-side-peek.tsx
component-registration/side-peek-component-registration-enum.ts
component-registration/side-peek-component-props-map.ts
component-registration/side-peek-component-registery.ts
component-registration/side-peek-component-mapper.ts
event-types/side-peek.ts
event-map/side-peek-event-map.ts
payload/side-peek-event-payload.ts
hooks/use-side-peek-emitter.ts
hooks/use-manage-side-peek-visibility.ts
hooks/use-close-side-peek-emitter.ts
```

## Adding a side-peek

When adding a side-peek, complete all steps:

1. Create the side-peek content component under the owning side-peek feature folder.
2. Create its props interface in a local `types` or `definitions` folder.
3. Add a key to `SidePeekComponentRegistrationEnum`.
4. Add the key/props pair to `SidePeekComponentPropsMap`.
5. Add the component and typed props placeholder to `SidePeekComponentRegistry`.
6. Create/open through a local hook that uses `useSidePeekEmitter`.
7. Emit `SidePeek.SIDE_PEEK` with the enum key and full typed props.
8. Include `title`, `is_open`, close behavior, and footer props as required by the base side-peek flow.

Do not manually render `SidePeek` from arbitrary game components unless you are changing the generic shell itself.

## Side-peek emitter pattern

Use local open hooks like the existing map/inventory hooks:

```ts
const sidePeekEmitter = useSidePeekEmitter();

sidePeekEmitter.emit(
  SidePeek.SIDE_PEEK,
  SidePeekComponentRegistrationEnum.LOCATION_DETAILS,
  {
    title: 'Location Details',
    is_open: true,
    ...props,
  }
);
```

Rules:

- use typed props;
- keep open logic in a hook;
- do not duplicate emitter setup inside every button;
- include accessible title text;
- include close callbacks where the side-peek content must clean up state.

## Side-peek shell accessibility

The generic side-peek shell must behave like an accessible dialog.

Required:

- `role="dialog"`;
- `aria-modal="true"`;
- title association through `aria-labelledby` or `aria-label`;
- Escape close support;
- visible close button;
- focus management in effects, not render;
- background overlay hidden from assistive technology;
- background scroll lock when open;
- mobile full-width layout;
- scrollable content region inside the panel.

Do not focus the dialog directly during render.

## Overlay footer rules

`BaseSidePeek` owns generic footer behavior.

Footer actions should be passed through props.

Rules:

- secondary action defaults can close the side-peek;
- primary action should be optional;
- labels must be explicit and accessible;
- danger actions should use danger button variants;
- do not place feature-specific footer logic inside the generic `SidePeek` shell.

## Screen/side-peek checklist

A screen or side-peek change is acceptable when:

- the correct pattern was chosen: screen vs side-peek;
- all registry/props map files were updated;
- bindings/open hooks are typed;
- copied dedupe keys/constants were corrected;
- close/back behavior works;
- overlay/dialog accessibility is preserved;
- mobile full-width side-peek behavior is preserved;
- hidden animated content is not keyboard reachable.
