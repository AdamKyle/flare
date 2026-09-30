import { MonsterGemEffectContextDefinition } from '../../definitions/monster-detail-definition';

export default interface UsePlayerMonsterGemEffectContextsDefinition {
  context_rows: MonsterGemEffectContextDefinition[];
  loading: boolean;
  loading_more: boolean;
  error: string | null;
  has_more: boolean;
  load_next: () => void;
}
