import { MonsterNavigationDefinition } from './monster-detail-props';
import MonsterDetailDefinition from '../api/definitions/monster-detail-definition';

export default interface MonsterNormalTabPanelProps {
  monster: MonsterDetailDefinition;
  navigation?: MonsterNavigationDefinition;
}
