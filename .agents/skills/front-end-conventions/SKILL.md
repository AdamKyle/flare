---
name: front-end-conventions
description: Use this skill when writing, reviewing, or refactoring React/TypeScript frontend code in this repository.
---

# Front End Conventions

## Scope

Use this skill for `resources/js/**` and related frontend styling/config only.

Before changing code, inspect the existing feature folder, nearby components, hooks, types, API hooks, enums, utils, styles, screen bindings, side-peek registration, provider usage, and current naming patterns.

Do not invent architecture when a nearby pattern already exists.

## Available Commands

Use the commands defined in `package.json`.

```bash
yarn dev
yarn cleanup
yarn build
yarn build:dev
yarn lint
yarn type-check
yarn unused-files-check
```

There is no frontend `test` script in the inspected `package.json`.

Node version is defined by `.nvmrc` as:

```bash
20.5.1
```

## Directory Structure

Frontend code lives under:

```text
resources/js
```

Top-level frontend areas:

```text
resources/js/admin
resources/js/api-handler
resources/js/configuration
resources/js/event-system
resources/js/game
resources/js/game-data
resources/js/screen-manager
resources/js/service-container
resources/js/service-container-provider
resources/js/ui
resources/js/utils
resources/js/websocket-handler
```

Use these layers as they exist:

```text
ui
```

Shared, generic UI primitives only. Examples: buttons, alerts, cards, containers, dropdowns, tabs, side-peek shell, tooltips, loading bars.

```text
api-handler
```

Shared API transport. Do not call axios directly from feature components. Use `useApiHandler()` through `ApiHandlerProvider`.

```text
event-system
```

Shared frontend event bus for cross-component UI events.

```text
websocket-handler
```

Echo/Pusher setup and websocket hooks.

```text
game-data
```

Loaded game state and websocket-updated game data.

```text
screen-manager
configuration/screen-manager
```

Typed app screen stack/navigation system.

```text
game/components
```

Game-specific screens, panels, cards, actions, side peeks, chat, shop, character sheet, map, etc.

```text
game/reusable-components
```

Reusable game-domain components. Use this for game-specific reuse, not generic UI.

```text
admin/<feature>
```

Admin-only feature apps. Follow the guide quest structure: `api`, `components`, `definitions`, `form-components`, `hooks`, `types`, `utils`.

Do not normalize existing folder names such as `deffinitions`, `deffintions`, or `registery` unless explicitly requested. Match the folder spelling used by the target area.

## Entry Points And Provider Layout

Main game entry:

```text
resources/js/app.ts
resources/js/game/game-launcher.tsx
resources/js/game/game.tsx
```

Admin entry:

```text
resources/js/admin-apps.ts
resources/js/admin/guide-quests/manage-guide-quest-base.tsx
```

The game provider order is:

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

Do not add another top-level provider unless the state/service is truly app-wide infrastructure.

Admin apps should use only the providers they need. The guide quest app uses:

```tsx
<ServiceContainer>
  <ApiHandlerProvider>
    <ManageGuideQuestsForm />
  </ApiHandlerProvider>
</ServiceContainer>
```

## Context Rules

Use existing contexts before creating new ones.

Use `ApiHandlerContext` only through `useApiHandler()`.

Use `EventSystemContext` only through `useEventSystem()`.

Use `GameDataContext` only through `useGameData()`.

Use `EchoHandlerContext` only through websocket/echo hooks.

Use `AppScreenProvider` / `useScreenNavigation()` / `useBindScreen()` for full game screens.

Create a new app-level context only when:

```text
- multiple unrelated feature branches need the same state
- prop drilling would cross several layers
- the state is not just one component’s local UI state
- the state is not already handled by game-data, screen-manager, side-peek, websocket, or event-system
```

Do not create a giant context for one feature panel.

Do not put simple modal, tab, dropdown, form, or loading state into app context.

For game state loaded once and updated by websockets, prefer extending `GameDataProvider` only if the data is truly global game data.

For opening/closing screens, use event hooks plus screen bindings.

For opening/closing side peeks, use the side-peek event system and registry.

## Screen Manager Rules

Full-screen game views are registered through:

```text
resources/js/configuration/screen-manager/screen-manager-constants.ts
resources/js/configuration/screen-manager/screen-manager-props.ts
resources/js/configuration/screen-manager/screen-manager-registry.ts
resources/js/game/screen-bindings
```

To add a new screen:

```text
1. Add the screen key to `Screens`.
2. Add its props interface to `AppScreenPropsMap`.
3. Add the component to `appScreenRegistry`.
4. Add a binding under `game/screen-bindings/<domain>-bindings`.
5. Add that binding to `game/screen-bindings/index.ts`.
```

Use a screen when the UI replaces or overlays the main game area through `ScreenHost`.

Do not use side peek registration for full-screen game sections.

Do not create a new context just to open a screen.

## Side Peek Rules

Shared side peek shell:

```text
resources/js/ui/side-peek/side-peek.tsx
```

Game side peek runtime:

```text
resources/js/game/components/side-peeks/base
```

To add a side peek:

```text
1. Create the component in the relevant side-peek feature folder.
2. Create a props interface in that feature’s `types` folder.
3. Add the enum key to `side-peek-component-registration-enum.ts`.
4. Add props to `side-peek-component-props-map.ts`.
5. Add component and props to `side-peek-component-registery.ts`.
6. Open it through `useSidePeekEmitter()`.
```

Use a side peek when the UI is a right-side overlay panel.

Do not add one-off side peek state inside random components.

Do not bypass `BaseSidePeek`.

## Component Placement

Use this placement order:

```text
resources/js/ui
```

Only generic reusable UI.

```text
resources/js/game/reusable-components
```

Reusable game-domain UI.

```text
resources/js/game/components/<feature>
```

Specific game feature UI.

```text
resources/js/admin/<feature>
```

Specific admin feature UI.

Inside feature folders, prefer the existing local folders:

```text
api
api/hooks
api/hooks/definitions
api/enums
api/definitions
components
form-components
hooks
hooks/definitions
types
utils
styles
enums
event-types
partials
```

Use `partials` for smaller pieces that belong to a larger feature component.

Use `components` for child components that are meaningful feature pieces.

Use `utils` for pure functions.

Use `styles` for reusable class builders or style maps.

Use `types` for component prop interfaces.

Use `hooks/definitions` for hook return/parameter interfaces.

## Naming

Files and folders use kebab-case:

```text
manage-guide-quests-form.tsx
use-fetch-guide-quest.ts
button-props.ts
screen-manager-constants.ts
```

Components use PascalCase:

```tsx
ManageGuideQuestsForm
CharacterSheet
ContainerWithTitle
```

Hooks use camelCase and start with `use`:

```tsx
useGameData
useApiHandler
useFetchGuideQuest
useManageFormSectionData
```

Event handlers start with `handle`:

```tsx
handleNextStep
handleUpdateFormData
handleSelectTab
handleClearSelection
```

Props interfaces end with `Props`:

```tsx
ButtonProps
CharacterSheetProps
TabsListProps
```

Hook return/parameter interfaces end with `Definition`, `Params`, or `State` based on the nearby pattern:

```tsx
UseFetchGuideQuestsDefinition
UseActivityTimeoutParams
UseCharacterSheetVisibilityState
```

API URL enums end with `ApiUrls`:

```tsx
GuideQuestApiUrls
GameLoaderApiUrls
```

Enum names use PascalCase. Enum members use uppercase when that is the local enum style:

```tsx
ButtonVariant.DANGER
Screens.CHARACTER_SHEET
SidePeekComponentRegistrationEnum.BACKPACK
```

Backend/API payload field names often remain snake_case because the frontend mirrors server responses and request payloads:

```tsx
guide_quest_id
on_close
is_open
allow_clicking_outside
character_id
```

Do not rename API-shaped fields to camelCase unless the surrounding code already transforms them.

## Interfaces And Types

Prefer `interface` for object shapes, props, API definitions, hook params, and hook return values.

Use `type` only when it is justified by TypeScript features that interfaces do not express cleanly:

```text
- unions
- mapped types
- conditional types
- utility compositions
- tuple-derived component props
- keyof/typeof expressions
```

Good justified `type` examples from the codebase:

```tsx
type ActiveKey = 'character' | 'craft' | 'map' | 'shop' | null;

export type SidePeekEventPayload = {
  [K in keyof SidePeekComponentPropsMap]: [K, SidePeekComponentPropsMap[K]];
}[keyof SidePeekComponentPropsMap];
```

Do not use `type` for simple component props unless the local file already needs type-level logic.

Use local `types` folders for component props.

Use `api/definitions` or `game/api-definitions` for API response/request shapes.

Keep interfaces small and specific.

## Hooks Versus Components

Create a hook when the code manages behavior or state:

```text
- API request state
- websocket subscription
- event emitter setup
- visibility state
- form state
- keyboard/mouse behavior
- derived behavior reused by a component
```

Create a component when the code renders UI.

Do not hide JSX-heavy UI inside hooks.

Do not put API request logic directly inside presentational components when an API hook pattern exists nearby.

Do not create a hook for a tiny one-off calculation that belongs in a component or pure utility.

Use pure utilities for stateless transformations:

```text
utils
param-builders
style builders
formatters
```

## API Call Rules

Do not import and call axios directly in features.

Use:

```tsx
const { apiHandler, getUrl } = useApiHandler();
```

URLs belong in local API enum files:

```text
api/enums/*-api-urls.ts
```

API hooks belong in:

```text
api/hooks
```

API hook interfaces belong in:

```text
api/hooks/definitions
```

Use `getUrl()` for route placeholders:

```tsx
getUrl(GameLoaderApiUrls.CHARACTER_SHEET, {
  character: characterId,
});
```

Use `useActivityTimeout()` where nearby API hooks handle 401 inactivity.

Use `AxiosError` checks before reading `response`.

Expose clean hook return values:

```tsx
return {
  data,
  loading,
  error,
  setRequestParams,
};
```

Do not return raw axios responses unless the existing local pattern does.

## useEffect And Dependencies

The project has `react-hooks/exhaustive-deps` enabled as a warning, but the code intentionally suppresses it in specific places.

Use only the dependencies the effect/callback is meant to react to.

When intentionally excluding values, use the existing comment exactly:

```tsx
// eslint-disable-next-line react-hooks/exhaustive-deps
```

Do not blindly accept exhaustive-deps suggestions if they change behavior, create duplicate API calls, restart websocket listeners incorrectly, or break request gating.

Do not omit actual dependencies that are required for correctness.

Effects should guard before doing work:

```tsx
if (!characterData) {
  return;
}
```

For one-time or gated execution, use refs like the existing patterns:

```tsx
const hasExecutedRef = useRef(false);
const inFlightRef = useRef(false);
```

## Early Returns And Conditional Rendering

Hooks must be called before component early returns.

The repo has a custom ESLint rule: no hooks after returns.

Use early returns after hooks for loading, empty, and error states:

```tsx
if (loading) {
  return <InfiniteLoader />;
}

if (error) {
  return <ApiErrorAlert apiError={error.message} />;
}
```

Always use braces for conditionals.

Do not use one-line `if`.

For complex JSX or branching, extract render helpers:

```tsx
const renderTitle = () => {};
const renderFooter = () => {};
const renderTopScreen = () => {};
const renderCharacterSheetScreen = () => {};
```

Use `ts-pattern` where the surrounding code uses it for multi-branch UI selection.

Do not leave large nested conditional JSX inline.

## UI And Styling

Use Tailwind utility classes directly in JSX for layout and component styling.

Use `clsx` for conditional classes.

Use style helper files when a component has reusable class maps:

```text
ui/buttons/styles/button/base-styles.ts
ui/buttons/styles/button/variant-styles.ts
```

Use Tailwind theme values from:

```text
resources/css/tailwind.css
```

Defined breakpoints are:

```text
2xsm: 360px
xsm: 375px
sm: 640px
md: 768px
lg: 1024px
xl: 1280px
2xl: 1536px
3xl: 1920px
```

Use existing responsive patterns:

```text
sm:hidden
md:w-1/2
lg:w-1/4
xl:w-2/3
max-sm:text-xs
```

Dark mode is class-based through the custom Tailwind variant:

```css
@custom-variant dark (&:is(.dark *));
```

Use existing color tokens such as:

```text
danube
rose
emerald
mango-tango
marigold
wisp-pink
gray
primary
```

Do not introduce random hard-coded colors when a theme color exists.

For generic containers, prefer existing wrappers:

```tsx
ContainerWrapper
WideContainerWrapper
ContainerWithTitle
Card
```

For mobile bottom navigation, preserve the existing `mobile-bottom-nav`, `mobile-bottom-nav-spacer`, `mobile-shell`, and safe-area behavior.

## Imports

Follow the configured import ordering.

Use aliases defined in `tsconfig.json` and `vite.config.js`:

```text
configuration/*
event-system/*
game-data/*
game-utils/*
api-handler/*
ui/*
service-container/*
service-container-provider/*
screen-manager/*
```

Prefer existing aliases for shared layers.

Use relative imports for nearby feature files.

Keep imports explicit.

Remove unused imports when editing.

## Comments

Do not add narrative comments or inline comments unless explicitly requested.

Do not remove existing comments unless explicitly requested.

When a dependency-array suppression is required, use the existing eslint-disable comment and keep it directly above the dependency array.

## Accessibility

Preserve existing accessibility patterns:

```text
aria-label
aria-hidden
aria-selected
aria-controls
aria-labelledby
aria-modal
role="dialog"
role="tablist"
role="tabpanel"
```

Interactive elements should be real buttons when clickable.

Buttons should include `type="button"` unless the form behavior requires otherwise.

## Service Container

Shared services are registered through:

```text
resources/js/configuration/modular-container.ts
resources/js/service-container/core-container.ts
```

Existing registered service containers:

```text
eventServiceContainer
axiosServiceContainer
echoServiceContainer
```

Do not use the service container for feature-local state.

Use it only for shared service instances like API handler, event system, or Echo initialization.

## Websockets

Use `useWebsocket()` for Echo subscriptions.

Use websocket hooks under the feature when the subscription is feature-specific.

Use the websocket provider already mounted in `Game`.

Do not initialize Echo manually inside feature components.

## Admin Feature Pattern

For admin feature apps, follow the guide quest structure:

```text
admin/guide-quests/api
admin/guide-quests/api/hooks
admin/guide-quests/api/enums
admin/guide-quests/api/definitions
admin/guide-quests/components
admin/guide-quests/form-components
admin/guide-quests/hooks
admin/guide-quests/types
admin/guide-quests/utils
```

Mount the app from a small base file that reads DOM data attributes and renders the root component.

Keep form-section behavior in hooks.

Keep form UI split into small components.

Keep request-object construction in utils when it is transformation logic.

## Quality Gates

Before considering frontend work complete, the relevant checks are:

```bash
yarn cleanup
yarn lint
yarn type-check
yarn build
yarn unused-files-check
```

`yarn cleanup` runs Prettier and ESLint fix over `resources/js/**/*.{ts,tsx}`.

`yarn lint` runs ESLint over `resources/js/**/*.{ts,tsx}`.

`yarn type-check` runs `tsc --noEmit --skipLibCheck`.

`yarn unused-files-check` runs `unimported`.

Do not claim tests were run unless a test command exists and was actually run.
