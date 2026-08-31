/**
 * Discriminated union describing which related entity's canonical detail is
 * currently stacked (via `StackedCard`) over an Admin Quest detail. Only the
 * relationship types the factual Quest presentation actually exposes.
 */
export type QuestNestedSelection =
  | { type: 'quest'; id: number }
  | { type: 'item'; id: number }
  | { type: 'monster'; id: number }
  | { type: 'npc'; id: number }
  | { type: 'map'; id: number };
