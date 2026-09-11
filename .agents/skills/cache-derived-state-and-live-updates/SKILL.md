---
name: cache-derived-state-and-live-updates
description: Use when adding or changing shared caches, Character-specific derived caches, cache invalidation/versioning, websocket-driven cache consumers, or performance-sensitive read models.
---

# Cache, Derived State, and Live Updates

## Shared source versus Character-derived state

Keep globally reusable/source data separate from Character-specific derived data.

A shared cache should contain facts/effects common to every consumer of the same domain context.

Character-specific modifiers must not force precomputation of every Character × context combination during a global cache build.

Prefer a small Character/context modifier profile plus a lazy derived cache when the derived result is expensive enough to reuse.

## Versioned invalidation

When a shared source cache can be rebuilt from imported/admin data, derive Character-specific cache identity from a shared revision/version rather than enumerating and deleting every Character cache.

A rebuild should make stale derived keys unreachable immediately; stale physical keys may expire naturally according to the repository cache strategy.

When Character progression/equipment/state changes the derived result, change/invalidate only that Character/context identity.

## Hot-path rule

Performance-sensitive requests should normally be:

1. resolve identity;
2. read cache;
3. return a small response.

Expensive transformation belongs on cache miss, mutation, import/rebuild, or an explicit asynchronous boundary—not on every list/stat/battle request.

Do not fetch large unrelated datasets merely to answer a small read.

## One authoritative derived path

If list, detail, manual battle, and automation/exploration need the same effective domain result, they must consume the same derived service/cache contract.

Do not calculate the same Character modifier separately in each caller.

## Live UI rule

A cache mutation or progression mutation that changes currently visible Character/game state must publish through the existing authoritative websocket/game-data path when such a path exists.

Do not add polling or forced page refresh to compensate for missing invalidation/live events.

The mounted global owner updates authoritative state; feature components consume that state or focused paginated reads.

## Small response rule

Return only what the current surface needs.

Use append pagination/infinite scroll for potentially growing collections. Do not embed entire historical/relationship collections into a detail response because they are convenient to fetch on the backend.
