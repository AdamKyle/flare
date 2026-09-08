export type ItemNestedSelection =
  | { type: 'location'; id: number }
  | { type: 'npc'; id: number }
  | { type: 'quest'; id: number }
  | { type: 'monster'; id: number }
  | { type: 'map'; id: number };
