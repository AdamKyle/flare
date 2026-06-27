---
name: front-end-performance-motion-and-loading
description: Use when changing Flare frontend performance-sensitive game UI, loading states, realtime update rendering, framer-motion animations, progress bars, infinite scroll, and render cost.
---

# Flare Performance, Motion, and Loading Rules

Use this skill when changing performance-sensitive game UI, realtime update rendering, loading states, progress bars, infinite scroll, motion transitions, or frequently updated frontend components.

## Core rule

Flare is a game UI. Frequent updates must stay responsive.

Do not make reward/game/data flows feel sluggish.

Do not add heavy rendering to hot paths.

Do not block UI updates with avoidable synchronous work in render.

## Hot path awareness

Be careful in areas that update often:

- game data provider updates;
- character stat bars/details;
- monster list and combat sections;
- announcements;
- chat;
- inventory/crafting state;
- map actions;
- timers/progress bars;
- websocket event handlers;
- infinite lists.

Keep these components small and focused.

## Render performance rules

Avoid:

- expensive transformations inside JSX;
- sorting/filtering large arrays directly in render without memoization;
- recreating large object/array literals passed to memoized children;
- setting state during render;
- updating global game state for local UI-only state;
- hiding large UI trees with CSS while still making them interactive;
- unnecessary parent re-renders from overly broad state placement.

Prefer:

- pure utilities for transformations;
- `useMemo` for expensive derived values;
- `useCallback` only when it solves a real dependency/child render problem;
- localized state;
- small child components;
- stable websocket handlers through existing hook patterns.

## Loading states

Use existing loading/progress components before creating new ones.

Relevant folders:

```text
resources/js/ui/loading-bar
resources/js/ui/progress
resources/js/ui/infinite-scroll
resources/js/game/components/game-loader
```

Loading states must:

- be accessible;
- avoid layout jumps where possible;
- show clear text when the wait is meaningful;
- not block unrelated UI;
- preserve user input when refreshing data;
- distinguish initial load from loading more.

## Infinite scroll

Use the shared paginated API hook and infinite scroll components where appropriate.

Rules:

- initial loading and loading-more are separate states;
- use `onEndReached` from the paginated hook;
- do not manually increment page from UI when the hook owns it;
- guard against duplicate load-more calls;
- keep screen-reader users informed when more content loads;
- provide a non-visual indication of end-of-list where useful.

## Progress bars and timers

Progress/timer UI must be clamped and accessible.

Rules:

- clamp percentages from 0 to 100;
- expose progress semantics for determinate progress;
- use text labels for meaning;
- avoid `calc()`;
- inline dynamic width is acceptable for computed progress;
- do not use color alone to show danger/success/remaining time.

## Framer Motion rules

Flare uses `framer-motion` for screen transitions and step transitions.

Rules:

- keep animations short and purposeful;
- avoid animating hot-path realtime updates unless necessary;
- mark hidden panels/screens with `aria-hidden`;
- disable pointer events on hidden panels/screens;
- do not leave focusable controls reachable inside hidden motion elements;
- do not animate in a way that causes mobile layout jank;
- do not add large motion wrappers around every list item unless necessary.

## Websocket update performance

When handling websocket payloads:

- update only the state slice that changed;
- preserve previous state where possible;
- avoid deep cloning large trees unnecessarily;
- avoid parsing/formatting large payloads in render after every event;
- keep event handlers small;
- move complex normalization into utilities.

## API and optimistic UI

Do not optimistically update global game state unless the API flow is designed for it.

For critical game actions, wait for API success and then update local/global state with the confirmed response.

Keep failure messages clear.

## Performance checklist

A performance-sensitive change is acceptable when:

- hot paths stay small;
- no state setters run during render;
- expensive derived data is memoized or moved to utilities;
- loading states are accessible and stable;
- infinite scroll uses existing hooks/patterns;
- motion does not break focus or mobile usability;
- websocket handlers update only what changed;
- global game data is not used for local UI state.
