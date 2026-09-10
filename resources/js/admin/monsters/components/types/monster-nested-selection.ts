import { MonsterGemEffectContextDefinition } from '../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';

export type MonsterNestedSelection =
  | { type: 'item'; id: number }
  | { type: 'map'; id: number }
  | { type: 'map_gem'; id: number }
  | { type: 'location_gem'; id: number }
  | { type: 'gem_effect_context'; context: MonsterGemEffectContextDefinition };
