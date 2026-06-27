---
name: front-end-project-layout-and-command-rules
description: Use before Flare frontend work to understand repository layout, mirrored skill rules, package scripts, allowed validation commands, and frontend boundaries.
---

# Flare Frontend Project Layout and Command Rules

Use this skill before any frontend task that creates, edits, reviews, refactors, or validates React, TypeScript, Tailwind, Vite, screen-manager, side-peek, API hook, game data, websocket, or admin frontend code.

## Core rule

Flare is a Laravel application with the frontend living inside the Laravel repository.

Frontend source does not live in `frontend/src`.

Frontend source lives in:

```text
resources/js
```

Styles live in:

```text
resources/css
```

The application boot path is:

```text
resources/js/app.ts
resources/js/bootstrap.ts
resources/js/game/game-launcher.tsx
```

The frontend build tool is Vite. The frontend package manager is Yarn. The package scripts are defined in `package.json` at the repository root.

## Mirrored skill rule

The project uses both Claude skills and agent skills.

Every frontend skill must exist in both folders with the exact same content:

```text
.claude/skills/<skill-name>/SKILL.md
.agents/skills/<skill-name>/SKILL.md
```

Do not update one skill tree without updating the other.

Do not put shared skill fragments outside `SKILL.md`. Each skill must be self-contained.

## Frontend source map

Use the current Flare layout:

```text
resources/js
├── admin
├── api-handler
├── configuration
│   └── screen-manager
├── event-system
├── game
│   ├── api-definitions
│   ├── components
│   ├── reusable-components
│   ├── screen-bindings
│   └── util
├── game-data
├── screen-manager
├── service-container
├── service-container-provider
├── types
├── ui
├── utils
│   └── hooks
└── websocket-handler
```

## Folder ownership

Use these boundaries:

- `resources/js/ui`: shared, generic UI primitives only.
- `resources/js/game/reusable-components`: reusable game-domain components that are not generic enough for `ui`.
- `resources/js/game/components`: game feature UI and game-domain behavior.
- `resources/js/game/components/side-peeks`: game side-peek content and side-peek registrations.
- `resources/js/game/screen-bindings`: bindings that connect visibility hooks/events to the screen manager.
- `resources/js/configuration/screen-manager`: app screen registry, screen names, and screen prop map.
- `resources/js/screen-manager`: generic reusable screen manager engine.
- `resources/js/api-handler`: shared API handler, paginated API handler, API context, API error display, and URL interpolation.
- `resources/js/websocket-handler`: Echo/Reverb initialization and the generic `useWebsocket` hook.
- `resources/js/game-data`: global game data provider, game-wide websocket wires, and global game context.
- `resources/js/event-system`: local event emitter system used by side-peeks and other decoupled UI events.
- `resources/js/admin/<feature>`: admin-only React feature applications.
- `resources/js/utils/hooks`: cross-feature utility hooks.
- `resources/css/tailwind.css`: Tailwind v4 theme source of truth.
- `resources/css/styles.css`: global CSS entry point.

## Import rules

The project uses TypeScript path aliases from `tsconfig.json`.

Use aliases for cross-folder imports:

```text
configuration/*
event-system/*
game-data/*
game-utils/*
api-handler/*
ui/*
service-container/*
service-container-provider/*
websockets
screen-manager/*
```

Use relative imports for files inside the same local feature/component folder.

Do not replace clear aliases with brittle deep relative imports.

Do not add a new alias unless the task explicitly requires it and both TypeScript and ESLint configuration are updated together.

## Package scripts

Use the existing package scripts:

```bash
yarn cleanup
yarn lint
yarn type-check
yarn build
yarn build:dev
yarn unused-files-check
```

Meanings:

- `yarn cleanup`: runs Prettier and ESLint fix over `resources/js/**/*.{ts,tsx}`.
- `yarn lint`: runs ESLint over `resources/js/**/*.{ts,tsx}`.
- `yarn type-check`: runs `tsc --noEmit --skipLibCheck`.
- `yarn build`: production Vite build.
- `yarn build:dev`: development Vite build.
- `yarn unused-files-check`: runs `unimported`.

There is no frontend test script in `package.json`. Do not claim frontend tests ran unless a real test command is added or provided by the user.

## Validation order

For frontend-only work, prefer this order:

```bash
yarn cleanup
yarn lint
yarn type-check
yarn build:dev
```

Run `yarn unused-files-check` when files are moved, deleted, renamed, or when import cleanup matters.

Run `yarn build` when the change could affect production build behavior.

Do not run dependency installation commands unless the user explicitly asks for dependency changes.

Do not use `npm`, `pnpm`, or `bun` in this repo.

## Dependency rule

Do not add, remove, upgrade, or lock frontend dependencies unless the user explicitly asks for dependency work.

Before adding a dependency, check whether the project already has a suitable package:

- React 19
- Vite 6
- TypeScript
- Tailwind CSS v4
- `clsx`
- `framer-motion`
- `@headlessui/react`
- `axios`
- `laravel-echo`
- `pusher-js`
- `tsyringe`
- `ts-pattern`
- `react-markdown`
- `lexical`
- `recharts`
- Font Awesome classes in Blade/UI code
- `rpg-awesome` icons

## Frontend change boundaries

Frontend tasks should not modify backend PHP, migrations, queues, jobs, or database code unless the task explicitly spans frontend/backend behavior.

If a frontend API contract does not match the backend, identify the contract mismatch and state the backend endpoint or response shape that needs adjustment. Do not silently invent frontend mappings that hide backend bugs.

## Completion checklist

Before finishing frontend work, verify:

- files were placed under the correct `resources/js` owner folder;
- shared UI was not polluted with game/admin business logic;
- API calls go through hooks and `useApiHandler`;
- screen-manager/side-peek registrations are complete when relevant;
- Tailwind classes use project tokens;
- mobile-first layout is preserved;
- light and dark mode both work;
- accessibility and screen-reader behavior were considered;
- package scripts were run or clearly listed as not run;
- `.claude/skills` and `.agents/skills` remain mirrored when skill files changed.
