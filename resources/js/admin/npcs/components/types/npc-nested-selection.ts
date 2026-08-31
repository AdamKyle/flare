/**
 * Discriminated union describing which related entity's canonical detail is
 * currently stacked (via `StackedCard`) over an Admin NPC detail. Only the
 * relationship types the factual NPC presentation actually exposes.
 */
export type NpcNestedSelection =
  | { type: 'quest'; id: number }
  | { type: 'item'; id: number; on_changed?: () => void }
  | { type: 'map'; id: number };
