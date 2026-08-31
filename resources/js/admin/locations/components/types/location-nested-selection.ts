/**
 * Discriminated union describing which related entity's canonical detail is
 * currently stacked (via `StackedCard`) over an Admin Location detail. Only
 * the relationship types the factual Location presentation actually
 * exposes.
 */
export type LocationNestedSelection =
  | { type: 'item'; id: number; on_changed?: () => void }
  | { type: 'map'; id: number };
