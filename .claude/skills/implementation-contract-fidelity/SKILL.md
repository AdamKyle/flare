---
name: implementation-contract-fidelity
description: Use whenever a user supplies a detailed implementation contract, prompt, blueprint, acceptance criteria, or exact UI/workflow instructions so the implementation matches every requested behavior and topology without substitution, scope reduction, or self-selected stopping boundaries.
---

# Implementation Contract Fidelity

## The user's explicit contract is binding

When the user specifies where something belongs, how it behaves, which existing system it reuses, what it is called, or what must not happen, implement that exact contract.

Do not replace an explicit requirement with something merely similar, easier, or already available.

Examples of forbidden substitutions:

- SidePeek instead of explicitly requested tabs;
- local nested card instead of explicitly requested ScreenManager stacking;
- polling/refetch instead of explicitly requested websocket live state;
- a new calculation engine instead of an explicitly named authoritative service;
- a chooser when the requirement says contextual single choice;
- eager bulk loading when append pagination/infinite scroll was required;
- a partial implementation described as a clean checkpoint.

## No self-selected scope reduction

Do not classify a requested item as:

- later work;
- browser-QA follow-up;
- another phase;
- another session;
- nice-to-have;
- too large for the current task;
- requiring a deeper future investigation;

unless the user explicitly made that classification.

If the implementation contract contains ten sections, completion means all ten sections are implemented and verified.

## Local verification is allowed; architecture reopening is not

Inspect exact current files, signatures, routes, payloads, and callers before editing. Adjust implementation details to current code when necessary.

Do not use local verification as permission to reopen decisions already fixed by the contract.

If current code directly contradicts the contract in a way that makes implementation impossible, report the exact contradiction. Otherwise implement the contract.

## Acceptance matrix before completion

Before declaring completion, construct an internal item-by-item checklist from the user's contract and prove each item from the final code.

For each item verify:

- exact behavior exists;
- exact ownership/layer is correct;
- required reuse path is used;
- explicitly forbidden behavior is absent;
- tests cover the owning business rule where applicable;
- requested UI topology is exact;
- live-state/performance requirements are preserved.

Do not rely on proof-of-work prose as evidence when the code disagrees.

## Proof-of-work accuracy

When `proof_of_work.md` is required, it records facts after implementation. It does not redefine the contract.

Never use proof-of-work text to justify an omitted requirement.
