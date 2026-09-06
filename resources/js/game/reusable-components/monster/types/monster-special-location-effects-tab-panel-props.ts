import { MonsterNavigationDefinition } from './monster-detail-props';
import { MonsterGemEffectContextDefinition } from '../api/definitions/monster-detail-definition';

export default interface MonsterSpecialLocationEffectsTabPanelProps {
  contexts: MonsterGemEffectContextDefinition[];
  navigation?: MonsterNavigationDefinition;
}
