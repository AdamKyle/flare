---
name: front-end-admin-features
description: Use when building or refactoring Flare admin frontend apps, admin guide quest forms, admin API hooks, admin field sections, admin validation, and admin-only UI flows.
---

# Flare Admin Frontend Features

Use this skill when creating, changing, reviewing, or refactoring admin frontend features under `resources/js/admin`.

## Admin source location

Admin React features live under:

```text
resources/js/admin/<feature>
```

Current strong reference:

```text
resources/js/admin/guide-quests
```

Use this as the model for admin form-heavy features.

## Admin feature structure

Use this layout when applicable:

```text
resources/js/admin/<feature>
├── <feature>.tsx
├── manage-<feature>.tsx
├── manage-<feature>-base.tsx
├── api
│   ├── definitions
│   ├── enums
│   └── hooks
│       └── definitions
├── components
│   └── types
├── form-components
│   └── types
├── hooks
│   └── definitions
├── definitions
├── types
└── utils
```

Only create folders that are needed.

## Admin API hooks

Admin API hooks follow the same API hook rules as game features.

Required:

- endpoint enum in `api/enums`;
- request/response/entity definitions in `api/definitions`;
- hook return/params definitions in `api/hooks/definitions`;
- hook uses `useApiHandler` and `getUrl`;
- components call hooks, not Axios;
- API fields mirror backend snake_case payloads.

Do not hard-code admin API URLs in form components.

## Admin form sections

Large admin forms must be split into section components.

Guide quest examples:

```text
manage-guide-quests-basic-quest-attributes.tsx
manage-guide-quests-required-class-ranks-attributes.tsx
manage-guide-quests-required-currencies.tsx
manage-guide-quests-required-item-attributes.tsx
manage-guide-quests-required-kingdom-attributes.tsx
manage-guide-quests-required-levels.tsx
manage-guide-quests-required-quest-and-plane-attributes.tsx
manage-guide-quests-required-stats.tsx
manage-guide-quests-rewards-and-bonuses.tsx
```

Rules:

- each section gets typed props;
- section components render fields only for that domain section;
- root/base components wire sections together;
- form utilities build/normalize the request object;
- validation is centralized enough to reason about.

## Admin form state

Use feature hooks for state management when the form has multiple sections or complex save behavior.

Hook definitions live in:

```text
hooks/definitions
```

Do not spread state mutation logic across many unrelated components.

Pass section-specific state and callbacks to child components.

## Admin text/content editors

When admin content uses rich text/markdown, use existing editor components/patterns.

Do not introduce a new editor library without explicit dependency approval.

Keep editor content accessible:

- label the editor;
- expose errors;
- preserve keyboard operation;
- handle paste/markdown behavior through existing editor utilities.

## Admin validation

Admin forms must validate before submit.

Validation must produce specific messages.

For large forms, use:

```text
hooks/use-<feature>-validation.ts
utils/validate-<feature>.ts
```

Validation should know the domain, but rendering components should not contain giant validation functions.

## Admin access assumptions

Frontend admin UI is not the security boundary.

Do not rely only on hidden buttons or routes for authorization.

When changing admin-only flows, ensure the frontend calls the proper admin endpoints and displays permission errors cleanly.

Do not expose admin-only actions in shared game UI unless the backend endpoint and user role checks are correct.

## Admin accessibility

Admin users still need fully accessible UI.

Rules:

- labels for all fields;
- accessible errors;
- keyboard navigable complex forms;
- focus/scroll to errors after failed validation;
- no hover-only controls for required actions;
- clear button labels;
- screen-reader-friendly loading and save states.

## Admin styling

Admin features use the same Tailwind theme, light/dark mode, and mobile-first rules.

Do not create a separate visual system unless the task explicitly asks for it.

Use shared UI primitives when they fit.

Use admin-specific components only when the UI is not generic.

## Admin checklist

An admin frontend change is acceptable when:

- code lives under `resources/js/admin/<feature>`;
- large forms are split into sections;
- API hooks are typed and use `useApiHandler`;
- request/response definitions mirror backend payloads;
- form state and validation are centralized;
- user-facing errors are clear and accessible;
- backend permission errors are handled;
- shared UI is reused without leaking admin business logic into `ui`.
