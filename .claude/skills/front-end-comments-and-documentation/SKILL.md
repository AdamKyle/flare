---
name: front-end-comments-and-documentation
description: Use for every touched TypeScript/React application file to keep comments and JSDoc concise, factual, and limited to non-obvious domain or integration constraints.
---

# Front End Comments And Documentation

## Default rule

Names, TypeScript types, props contracts, hooks, utilities, and component structure should explain ordinary code.

Do not add JSDoc or inline comments merely because a component, hook, interface, enum, constant, or utility exists.

When touching a frontend file, remove stale or redundant narrative comments in the touched area.

## Keep comments only for non-obvious constraints

A comment is justified when it records a factual constraint that cannot be made clear enough through naming/types/structure, such as:

- compatibility behavior that intentionally differs from the obvious implementation;
- an external integration limitation;
- a non-obvious accessibility requirement tied to a browser/library behavior;
- an authoritative domain invariant that would otherwise look like a bug;
- a deliberate workaround whose cause remains active and verifiable.

Keep such comments short and state the constraint, not an implementation diary.

## Prohibited frontend commentary

Do not add or preserve in touched code:

- implementation-history essays;
- phase/ticket/task references;
- comments saying a component is `canonical`, `shared`, `modern`, `polished`, or `reusable` when the code/location already proves it;
- comments narrating obvious JSX or state updates;
- comments that restate a prop/interface field;
- step-by-step comments around straightforward code;
- future plans/TODOs unless the task explicitly requires a tracked TODO;
- comments that justify a rule not actually proven by current code;
- duplicate JSDoc above a component whose name and props already describe it.

## Components and hooks

Do not write boilerplate such as:

- `Render the location detail component.`
- `Hook used to fetch quests.`
- `Props for the monster card.`
- `Handle the close button click.`

Prefer clear names such as `LocationDetailBody`, `useQuestDetailApi`, `MonsterCardProps`, and `handleClose`.

## Complex utilities

A pure utility may have a concise comment only when its input/output relationship or domain formula is not apparent from types/naming.

Do not use comments to compensate for an overlong or mixed-responsibility function. Refactor the structure instead when the touched code violates the relevant component/utility skills.

## Review checklist

For every touched frontend comment/JSDoc ask:

1. Is this fact non-obvious from code and types?
2. Is it still true?
3. Does it explain a domain/integration constraint rather than narrate implementation?
4. Can clearer naming/structure remove the need for it?

If the answer does not justify the comment, remove it.
