export type LocationNestedSelection =
  | { type: 'item'; id: number; on_changed?: () => void }
  | { type: 'map'; id: number };
