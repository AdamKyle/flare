export type QuestNestedSelection =
  | { type: 'quest'; id: number }
  | { type: 'item'; id: number }
  | { type: 'monster'; id: number }
  | { type: 'npc'; id: number }
  | { type: 'map'; id: number };
