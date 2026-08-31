/**
 * Discriminated union describing which related entity's canonical detail is
 * currently stacked (via `StackedCard`) over an Admin Item detail. Only the
 * relationship types the factual quest Item presentation actually exposes.
 */
export type ItemNestedSelection =
  | { type: 'location'; id: number }
  | { type: 'npc'; id: number }
  | { type: 'quest'; id: number }
  | { type: 'monster'; id: number }
  | { type: 'map'; id: number };
