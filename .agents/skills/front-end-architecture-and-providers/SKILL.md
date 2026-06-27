---
name: front-end-architecture-and-providers
description: Use when changing Flare frontend bootstrapping, providers, service container wiring, context boundaries, app shell layout, or global frontend architecture.
---

# Flare Frontend Architecture and Providers

Use this skill when changing frontend architecture, application bootstrapping, provider order, global contexts, service containers, game shell layout, or app-wide frontend behavior.

## Architecture summary

Flare uses a Laravel/Vite frontend mounted from `resources/js/app.ts`.

The game frontend is a React application with service-container based dependency registration, API context, event-system context, Echo/Reverb websocket context, global game-data context, a global side-peek host, and a stack-based screen manager.

The relevant provider shape is:

```tsx
<ServiceContainer>
  <EventSystemProvider>
    <ApiHandlerProvider>
      <EchoHandlerProvider>
        <GameDataProvider>
          <BaseSidePeek />
          <AppScreenProvider>
            <GameSection />
          </AppScreenProvider>
        </GameDataProvider>
      </EchoHandlerProvider>
    </ApiHandlerProvider>
  </EventSystemProvider>
</ServiceContainer>
```

Keep provider order intentional.

Do not bypass providers by importing service instances directly into components.

## Service container rules

The service container lives under:

```text
resources/js/service-container
resources/js/service-container-provider
```

Feature code should use provider hooks and context hooks rather than manually constructing global services.

The API handler is registered through:

```text
resources/js/api-handler/axios-service-container.ts
```

The Echo initializer is registered through:

```text
resources/js/websocket-handler/echo-service-container.ts
```

Rules:

- register app-wide services in the service container only when they are truly shared;
- do not register feature-local state in the global container;
- do not instantiate `ApiHandler` or `EchoInitializer` inside feature components;
- do not import `serviceContainer().fetch(...)` into ordinary UI components;
- expose shared services through provider hooks.

## Context ownership

Use the correct context owner:

- API request access: `ApiHandlerProvider` and `useApiHandler`.
- Echo/Reverb access: `EchoHandlerProvider`, `useEchoInitializer`, and `useWebsocket`.
- Event emitters: `EventSystemProvider` and `useEventSystem`.
- Global game payload/state: `GameDataProvider` and `useGameData`.
- Screen navigation: `AppScreenProvider`, `useScreenNavigation`, `useBindScreen`, and `ScreenHost`.
- Side-peeks: `BaseSidePeek`, side-peek emitter hooks, and side-peek registry.

Do not add another context when an existing owner already models the concern.

## Game shell rules

`resources/js/game/game-section.tsx` owns the shell around:

- `ScreenBindingHost`
- base `GameCard`
- `ScreenHost`
- `GameChat`
- `MobileNav`
- game loader state

Rules:

- keep `GameSection` focused on shell composition;
- do not add feature request logic to `GameSection`;
- do not add screen-specific rendering directly to `GameSection`;
- add new full-screen game views through the screen manager;
- add new right-side overlays through the side-peek system;
- preserve the `mobile-shell` class when working around mobile keyboard/bottom-nav behavior.

## Global game data rules

`GameDataProvider` owns global game state that many features need, such as:

- character data;
- monster list;
- announcements;
- current character id;
- game-wide websocket update wires.

Use `GameDataProvider` for truly global game data only.

Do not put feature-local form state, local dropdown state, local visibility state, or one-off API response state into `GameDataProvider`.

Do not call state setters during render. Listener startup and state synchronization belong in `useEffect`, explicit event handlers, or dedicated hooks.

Bad pattern:

```tsx
if (!listening) {
  startListening();
}
```

Preferred pattern:

```tsx
useEffect(() => {
  if (listening) {
    return;
  }

  startListening();
}, [listening, startListening]);
```

## Event system rules

Use the event system when UI components need to communicate without direct parent/child coupling and when the event is already modeled that way in Flare.

Current examples:

- side-peek open/close;
- visibility hooks;
- decoupled game UI events.

Rules:

- define event enums for event names;
- define typed event payloads;
- fetch/create emitters through `useEventSystem`;
- subscribe/unsubscribe in effects;
- do not emit events during render;
- do not use the event system to avoid simple prop drilling in a small local component tree.

## Admin architecture

Admin React features live under:

```text
resources/js/admin/<feature>
```

Admin code can have its own feature structure, API hooks, form components, feature hooks, and utilities.

Admin code should still use shared `ui` components, `api-handler`, Tailwind theme tokens, and accessibility rules.

Admin features must not import game-only runtime contexts unless the admin feature is intentionally embedded inside the game runtime.

## Architecture decision checklist

Before adding architecture, ask:

- Is this global or feature-local?
- Does an existing provider/context already own this concern?
- Is this a full game screen, side-peek, admin form, or shared UI primitive?
- Will this run inside the game provider stack or an admin mount?
- Does this require API, websocket, event-system, or game-data wiring?
- Is the new abstraction reducing repeated code or hiding behavior?

Prefer boring, explicit local code over premature global abstractions.
