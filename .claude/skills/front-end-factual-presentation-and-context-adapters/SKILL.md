---
name: front-end-factual-presentation-and-context-adapters
description: Use when game-data presentation is or may be shared by Admin, Information/wiki, and Player/Character contexts, including factual detail bodies, SidePeeks, StackedCards, Character overlays, and related-entity navigation.
---

# Front End Factual Presentation And Context Adapters

## Architectural rule

One factual game-data presentation may be consumed by different contexts.

Keep these responsibilities separate:

- **Factual presentation**: receives typed factual data and callbacks; renders the domain information.
- **Admin adapter**: fetches Admin-safe data and owns mutation/edit/create/import/export controls.
- **Information adapter**: fetches public/read-only data and owns public routing/navigation.
- **Player/Character adapter**: fetches/receives Character-specific state and owns gameplay actions, ownership/readiness/completion, and Character-authorized mutations.

Do not fork a factual component merely because a second context consumes it.

## Presentational-by-default feature children

A feature/domain child component is presentational by default when its output can be determined from props.

Pass it:

- typed data;
- display flags already resolved by the owning adapter when the flag is context-specific;
- relationship callbacks;
- action callbacks when the same visual body exposes a context-supplied action slot;
- loading/disabled labels only when presentation needs them.

A factual body must not independently:

- call API hooks;
- construct API payloads or URLs;
- read Admin authorization;
- infer Character ownership/completion/history;
- perform mutations;
- subscribe to Character/game websocket state;
- decide application business eligibility when the backend/domain owns that decision;
- import an Admin feature root to obtain factual UI.

## State ownership

State is allowed only at the narrowest owner that genuinely needs it.

Valid local presentation state includes:

- an expanded/collapsed disclosure;
- selected visual tab when it changes presentation only;
- focus/accessibility state;
- local StackedCard selection needed for nested factual navigation;
- transient drag/drop/hover/file selection state in a control whose behavior is itself local UI.

Context/application state belongs above the factual body:

- API response/loading/error;
- selected Character;
- permission/ownership;
- mutation status;
- Character completion/readiness;
- websocket-authoritative state;
- route/query state;
- Admin edit/create state.

If a child can be a pure function of props, keep it a pure function of props.

## SidePeek and StackedCard

Use the existing SidePeek and StackedCard systems.

- A top-level contextual entry uses the existing SidePeek event/registry path.
- Related factual drill-down inside an open SidePeek uses local `StackedCard` composition.
- Do not create a second global SidePeek stack.
- Do not duplicate a factual body specifically for SidePeek versus show page when the information is the same.
- Show pages and SidePeek wrappers should compose the same factual body whenever their factual content is equivalent.
- Context-specific controls belong in the wrapper/adapter or explicit action slot, not inside permission-neutral factual content.

## Reusable placement

Use `resources/js/game/reusable-components/**` when the component knows game-domain concepts but must be reusable by Admin, Information/wiki, or Player/Character.

Keep Admin-only forms, mutation screens, editor controls, and import/export components under `resources/js/admin/**`.

Keep Character gameplay orchestration under `resources/js/game/components/**` and pass factual data/actions into reusable game-domain presentation.

Do not move generic UI primitives out of `resources/js/ui/**`.

## Backend authority

Frontend presentation must not recreate authoritative backend rules.

Examples include:

- class lock/unlock calculations;
- quest readiness/completion eligibility;
- item ownership/history;
- map traversal eligibility;
- boon active/expired state when backend/events are authoritative;
- equip/unequip restrictions.

Render the authoritative state/requirements supplied by the backend and still handle a backend rejection if state changes between render and action.

## Reuse review

Before accepting a show/detail/SidePeek component, verify:

1. Is the factual JSX duplicated in another wrapper?
2. Does a factual body fetch its own data when an adapter already can?
3. Does Admin mutation/auth leak into reusable factual content?
4. Does Player/Character state leak into factual content?
5. Can Player/wiki consume the body without importing Admin code?
6. Does nested factual navigation reuse existing StackedCard behavior?
7. Is local state genuinely presentation-owned?

If the answer reveals mixed contexts or duplicated factual JSX, split the adapter from the factual body without changing domain behavior.
