import { MonsterNavigationDefinition } from './monster-detail-props';
import { MonsterGemEffectContextDefinition } from '../api/definitions/monster-detail-definition';

export default interface MonsterGemEffectContextCardProps {
  context: MonsterGemEffectContextDefinition;
  navigation?: MonsterNavigationDefinition;
  single_column?: boolean;
}
