import { MonsterGemEffectContextDefinition } from '../../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';

export default interface UseMonsterGemEffectContextsDefinition {
  context_rows: MonsterGemEffectContextDefinition[];
  loading: boolean;
  loading_more: boolean;
  error: string | null;
  has_more: boolean;
  load_next: () => void;
}
