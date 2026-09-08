export type NpcNestedSelection =
  | { type: 'quest'; id: number }
  | { type: 'item'; id: number; on_changed?: () => void }
  | { type: 'map'; id: number };
