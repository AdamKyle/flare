/**
 * Discriminated union describing which related entity's canonical detail is
 * currently stacked (via `StackedCard`) over an Admin Monster detail. Only
 * the relationship types the factual Monster presentation actually exposes.
 */
export type MonsterNestedSelection =
  | { type: 'item'; id: number }
  | { type: 'map'; id: number }
  | { type: 'map_gem'; id: number }
  | { type: 'location_gem'; id: number };
