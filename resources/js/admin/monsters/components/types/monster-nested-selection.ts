export type MonsterNestedSelection =
  | { type: 'item'; id: number }
  | { type: 'map'; id: number }
  | { type: 'map_gem'; id: number }
  | { type: 'location_gem'; id: number };
