import { MonsterScreens } from './monster-screen-constants';
import { MonsterGemEffectContextDefinition } from '../../../game/reusable-components/monster/api/definitions/monster-detail-definition';

export type MonsterListScreenProps = Record<string, never>;

export interface MonsterShowScreenProps {
  monster_id: number;
}

export interface MonsterFormScreenProps {
  monster_id: number | null;
}

export interface MonsterGemEffectContextScreenProps {
  context: MonsterGemEffectContextDefinition;
}

export interface MonsterScreenPropsMap {
  [MonsterScreens.LIST]: MonsterListScreenProps;
  [MonsterScreens.SHOW]: MonsterShowScreenProps;
  [MonsterScreens.FORM]: MonsterFormScreenProps;
  [MonsterScreens.GEM_EFFECT_CONTEXT]: MonsterGemEffectContextScreenProps;
}

export type MonsterScreenName = keyof MonsterScreenPropsMap;
