---
name: front-end-project-layout-and-command-rules
description: Use before Flare frontend work to enforce repository layout, import aliases, package scripts, validation commands, dependency restrictions, and frontend boundaries.
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

The canonical Vite aliases are:

```text
configuration/*
event-system/*
api-handler/*
game-data/*
game-utils/*
components/*
ui/*
service-container/*
service-container-provider/*
screen-manager/*
```

When an import crosses into one of these aliased roots, use the alias. Do not write deep relative imports such as `../../../ui/...` or `../../../../api-handler/...` to reach an aliased root.

Use relative imports only for files within the same local feature/component area when no configured alias is crossed.

Vite, TypeScript, and ESLint resolution must agree for aliases used by touched code. If the current configurations disagree, align them as part of the touched frontend work rather than introducing an import workaround.

Do not invent a new alias without explicit user instruction.

## Package scripts and mandatory validation

The repository defines these relevant scripts in `package.json`:

```text
yarn cleanup
yarn lint
yarn type-check
yarn build
yarn build:dev
yarn unused-files-check
```

After code changes are complete, always run and require success from:

```bash
yarn lint && yarn type-check && yarn cleanup && yarn unused-files-check && ./vendor/bin/pint
```

For frontend implementation work, also run:

```bash
yarn build:dev
```

Treat ESLint warnings in changed code as violations even when the process exits zero. Do not add rule suppressions to make the output appear clean.

There is no frontend test script in `package.json`. Do not claim frontend tests ran unless a real test command is added or explicitly provided.

## Dependency rule

Do not install, add, remove, upgrade, downgrade, or replace dependencies without explicit user instruction.

Do not run `npm`, `pnpm`, or `bun` for this repository. Yarn is the frontend package manager.

Before any explicitly authorized dependency change, inspect `package.json` and existing project abstractions first.

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
- all mandatory quality gates completed successfully, or the exact factual blocker was reported;

## Repository metadata boundary

Frontend application work must not modify IDE/workspace/assistant metadata such as `.idea/**`, `.vscode/**`, or assistant task-state files. Those files are unrelated to application behavior unless explicitly requested.
