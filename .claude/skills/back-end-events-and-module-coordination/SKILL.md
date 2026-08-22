---
name: back-end-events-and-module-coordination
description: Use when Laravel events, listeners, jobs, broadcasts, or synchronous service calls coordinate behavior across app/Game modules.
---

# Back End Events and Module Coordination

## Choose Communication Deliberately

- Use a direct injected module contract when the caller needs a result, validation outcome, or immediate guarantee.
- Use an event only for a completed fact or an established UI broadcast that is already event-driven.
- Do not create request/command events such as `PerformActionRequested` merely to hide a direct dependency.
- Do not add listeners that secretly perform required steps of the initiating operation.
- Keep coordination visible in a focused owning service/orchestrator.

## Published Fact Events

When a cross-module fact event is justified:

- use a specific past-tense name;
- keep the payload small and stable;
- prefer identifiers, enums, and immutable scalar/value data;
- do not expose Eloquent models, query builders, internal services, or mutable collections as a new event contract;
- let each consuming module own its listener and reaction;
- make repeat delivery safe when the existing queue or broadcast path can repeat;
- preserve existing channel, event-name, payload, websocket, and frontend contracts.

Do not change an existing model-carrying broadcast solely for architectural purity when that would alter an established public websocket contract. Adapt new internal consumers at a deliberate boundary and keep the compatibility requirement explicit.

## Strict Transaction Prohibition

Do not introduce or recommend:

- `ShouldDispatchAfterCommit`;
- `ShouldQueueAfterCommit`;
- queue connection `after_commit` changes;
- `DB::afterCommit()` or equivalent callbacks;
- database transactions to coordinate events;
- an outbox, inbox, saga, or transactional-message relay.

These mechanisms require explicit user authorization for the exact change. Do not infer authorization from an article, framework capability, reliability goal, or existing unrelated code.

## Jobs and Failures

Jobs remain thin and call the owning service. Preserve Flare's non-throwing service result and lifecycle-failure conventions. Do not add retries, delays, middleware, or asynchronous behavior unless the task requires them.

## Tests

Test the observable public behavior: direct result contracts, event payload stability, listener ownership, idempotent behavior where repeat delivery is possible, and unchanged websocket/API output. Do not test implementation-detail dispatch choreography unless it is itself a required contract.
