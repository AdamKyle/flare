---
name: front-end-quality-gates-and-review-checklist
description: Use before finishing Flare frontend work to verify formatting, linting, type checking, build behavior, accessibility, mobile-first layout, dark mode, and mirrored skill consistency.
---

# Flare Frontend Quality Gates and Review Checklist

Use this skill before finishing frontend work.

## Required review mindset

Do not stop after the code compiles.

Review the change for:

- correct folder placement;
- API contract correctness;
- type safety;
- render readability;
- accessibility;
- mobile-first layout;
- light/dark mode;
- performance;
- screen-manager/side-peek registration completeness;
- validation and error handling;
- command results.

## Required validation commands

After code changes are complete, always run:

```bash
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```

For frontend implementation work, also run:

```bash
yarn build:dev
```

Every command must succeed. Resolve ESLint warnings/errors in changed code; do not treat warning-only output as acceptable compliance.

Do not claim frontend tests passed unless a real frontend test command exists and was run.

## Folder placement checklist

Verify:

- shared UI went under `resources/js/ui` only if generic;
- game-domain reusable UI went under `resources/js/game/reusable-components`;
- game feature code stayed under `resources/js/game/components/<feature>`;
- admin feature code stayed under `resources/js/admin/<feature>`;
- API hooks stayed inside the owning feature API folder;
- global provider code was changed only when truly global;
- utility code is pure and placed in local or shared utils appropriately.

## API checklist

Verify:

- no component calls Axios directly;
- no component hard-codes API URLs;
- endpoint enum exists;
- request/response/entity definitions exist;
- hook uses `useApiHandler` and `getUrl`;
- loading/error state is exposed;
- paginated data uses the paginated API handler when appropriate;
- snake_case backend fields were preserved;
- 401/inactivity behavior is handled consistently.
- no `console.*` logging/debugging/error output remains in touched application code;

## TypeScript checklist

Verify:

- no new `any`;
- object shapes use interfaces;
- unions/mapped types use type aliases;
- props live in `types` files;
- hook definitions live in `definitions` files when substantial;
- API contracts live in `api/definitions`;
- null/undefined handling is explicit;
- type assertions are rare and isolated;
- no non-null assertions hide missing data bugs.

## Component readability checklist

Verify:

- hooks are called before early returns;
- large conditional JSX is in render helpers;
- render helpers use early returns;
- handlers are named clearly;
- derived values make conditions readable;
- root feature components are not overloaded;
- feature components are extracted at useful boundaries;
- no major nested ternary JSX was added;
- JSX `.map()` callbacks remain visually trivial and render already-prepared data;
- non-trivial mapped display state is derived before JSX and passed to a named render function or focused component;
- mapped JSX does not contain nested ternaries or a cluster of local derived-state declarations.

## Accessibility checklist

Verify:

- every interactive control has an accessible name;
- icon-only buttons have `aria-label`;
- decorative icons have `aria-hidden="true"`;
- forms have labels;
- validation errors are accessible;
- loading/progress states expose status/progress semantics;
- keyboard-only operation works;
- focus states are visible;
- dialogs/side-peeks have dialog semantics and close behavior;
- hidden animated content is not keyboard reachable;
- color is not the only way to communicate state.

## Styling checklist

Verify:

- Tailwind theme tokens were used;
- no raw hex colors were added in JSX;
- mobile base layout works;
- responsive classes enhance larger screens;
- light and dark mode are both readable;
- focus/hover/disabled/selected states are styled;
- project palettes are used correctly;
- no unnecessary arbitrary values, `calc()`, or overflow hacks were added;
- class logic uses `clsx` or styles helpers when conditional.

## Screen-manager checklist

For full screens, verify:

- screen constant added;
- props map updated;
- registry updated;
- binding added where needed;
- binding uses the correct screen key;
- dedupe key is not copy/pasted incorrectly;
- close/back behavior works;
- hidden screens are inaccessible to keyboard/screen readers.

## Side-peek checklist

For side-peeks, verify:

- enum key added;
- props map updated;
- registry updated;
- open hook uses `useSidePeekEmitter`;
- emitted props are typed and complete;
- title is accessible;
- close behavior works;
- mobile full-width panel still works;
- focus/Escape behavior is preserved.

## Game-data/websocket checklist

Verify:

- Echo is accessed through existing hooks;
- event/channel names are enum-based;
- payloads are typed;
- subscription is guarded by `enabled` when IDs/data may be missing;
- cleanup is handled;
- global game state updates are immutable;
- no listener starts during render;
- local UI state did not move into `GameDataProvider`.

## Final response checklist

When reporting completion, include:

- what changed;
- files/areas changed;
- commands run and results;
- commands not run, if any;
- any risks or manual follow-up.

Keep it factual and specific.

## Strict implementation review additions

Before accepting touched frontend code, verify:

- no state setter or synchronization ref mutation occurs during render;
- no new forced type assertions hide contract mismatches;
- true integer form inputs reject decimal/trailing-junk strings rather than truncating them;
- every touched Axios request has correct abort/stale-response behavior;
- real API errors are not swallowed;
- closed backend enum values use explicit frontend label mappings rather than generic string humanization;
- requested icon-only controls use the project's actual icon-button pattern;
- governing skill files and repository IDE/tool metadata were not modified by application work.
