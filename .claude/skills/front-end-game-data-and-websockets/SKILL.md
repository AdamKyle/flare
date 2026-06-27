---
name: front-end-game-data-and-websockets
description: Use when changing Flare game-data context, character/monster/announcement updates, Laravel Echo/Reverb websocket hooks, event payloads, realtime wires, and global game state updates.
---

# Flare Game Data and Websocket Rules

Use this skill when adding, changing, or reviewing global game state, game-data context, character updates, monster updates, announcements, Laravel Echo/Reverb websocket subscriptions, websocket payloads, and realtime UI behavior.

## Core rule

Realtime game behavior must be explicit, typed, and isolated.

Do not initialize Echo manually inside feature components.

Do not subscribe to websockets directly in UI components when a feature hook or wire component should own it.

Do not update global game state from random child components unless the provider exposes an intentional method.

## Echo/Reverb infrastructure

Generic websocket infrastructure lives under:

```text
resources/js/websocket-handler
```

Important files:

```text
websocket-handler/echo-initializer.tsx
websocket-handler/components/echo-handler-provider.tsx
websocket-handler/hooks/use-echo-initializer.ts
websocket-handler/hooks/use-websocket.ts
websocket-handler/enums/channel-type.ts
websocket-handler/helpers/get-url.ts
```

Use `useWebsocket`.

Do not create a second Echo initializer.

Do not call `new Echo(...)` in feature code.

## Websocket hook shape

`useWebsocket` expects:

```ts
interface UseWebsocketParams<T> {
  url: string;
  params: Record<string, number>;
  type: ChannelType;
  channelName: string;
  onEvent: (data: T) => void;
  enabled?: boolean;
}
```

Rules:

- define payload type `T`;
- use `ChannelType.PRIVATE` or `ChannelType.PUBLIC`;
- use URL templates with params;
- use `enabled` to avoid subscribing before IDs/data are available;
- unsubscribe through the hook cleanup;
- keep event handlers stable enough to avoid excessive re-subscribing.

## Channel and event enums

Feature or global websocket events should use enum files.

Current global game-data examples live under:

```text
game-data/components/event-enums/core-web-socket-channels.ts
game-data/components/event-enums/core-web-socket-event-names.ts
```

Rules:

- do not scatter channel names as string literals;
- do not scatter event names as string literals;
- keep channel URL params clear;
- keep event payload definitions near the owner.

## Global game data owner

Global game state lives in:

```text
resources/js/game-data/components/game-data-provider.tsx
resources/js/game-data/hooks/use-game-data.ts
resources/js/game-data/deffinitions/game-data-context-definition.ts
resources/js/game-data/deffinitions/game-data-definition.ts
```

Global game data includes data such as:

- character;
- character id;
- monster list;
- announcements;
- has-new-announcements flag;
- provider methods for global updates.

Feature-local data does not belong in `GameDataProvider`.

## Game-data update rules

When updating global game data:

- use functional `setGameData` updates;
- preserve previous state;
- return previous state when required data is missing;
- keep updates immutable;
- merge partial character updates carefully;
- avoid wiping unrelated global game fields.

Good pattern:

```ts
setGameData((prev): GameDataDefinition | null => {
  if (!prev || !prev.character) {
    return prev;
  }

  return {
    ...prev,
    character: {
      ...prev.character,
      ...characterUpdate,
    },
  };
});
```

## Listener startup

Do not call listener startup functions during render.

Bad:

```tsx
if (!characterUpdatesListening) {
  startCharacterUpdates();
}
```

Good:

```tsx
useEffect(() => {
  if (characterUpdatesListening) {
    return;
  }

  startCharacterUpdates();
}, [characterUpdatesListening, startCharacterUpdates]);
```

Starting listeners should happen in effects or explicit user-driven handlers.

## Wire components

Use wire components/hooks for websocket subscriptions that update global data.

Examples:

```text
game-data/components/character-updates-wire.tsx
game-data/components/monster-updates-wire.tsx
game-data/components/announcement-updates-wire.tsx
game-data/hooks/use-character-updates.tsx
game-data/hooks/use-monster-updates.tsx
game-data/hooks/use-announcement-updates.tsx
```

Rules:

- wire components render `null` or hidden subscription wiring;
- hooks manage listening state;
- payload definitions live in hook definitions or API data definitions;
- event handlers are passed in from the provider or owning feature;
- UI components should not know low-level Echo details.

## IDs and meta tags

Game bootstrapping may read meta tags for player/character ids.

Rules:

- parse meta tag values safely;
- use `parseInt(value, 10)`;
- default to `0` only when the existing provider pattern expects it;
- do not subscribe to private channels until required IDs are valid;
- guard websocket hooks with `enabled`.

## Performance rules

Realtime updates can be frequent.

Do not:

- re-render large trees unnecessarily;
- recreate listener functions in tight render loops without reason;
- update global game data for feature-local UI state;
- append unbounded arrays without considering UI cost;
- perform expensive transformations inside websocket event handlers without memoization or utilities.

Prefer small immutable updates and focused child components.

## Websocket accessibility rule

Realtime UI updates must remain understandable.

For important user-visible changes:

- show text, not just color/animation;
- use polite live regions where appropriate;
- avoid stealing focus on every update;
- avoid forcing screen changes without user intent unless the game requires it.

## Websocket checklist

A websocket/game-data change is acceptable when:

- Echo is accessed through existing providers/hooks;
- channels/events are enum-based;
- payloads are typed;
- subscription is guarded by `enabled` when data is missing;
- cleanup is handled;
- global state updates are immutable;
- listener startup does not happen during render;
- feature-local state stays out of `GameDataProvider`;
- realtime UI remains accessible.
