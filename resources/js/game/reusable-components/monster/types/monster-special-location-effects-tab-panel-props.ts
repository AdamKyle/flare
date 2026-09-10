import { MonsterGemEffectContextDefinition } from '../api/definitions/monster-detail-definition';

export default interface MonsterSpecialLocationEffectsTabPanelProps {
  contexts: MonsterGemEffectContextDefinition[];
  loading: boolean;
  loading_more: boolean;
  error: string | null;
  has_more: boolean;
  on_load_next: () => void;
  on_open_context?: (context: MonsterGemEffectContextDefinition) => void;
}
