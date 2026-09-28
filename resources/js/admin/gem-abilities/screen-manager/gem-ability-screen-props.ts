import { GemAbilityScreens } from './gem-ability-screen-constants';

export type GemAbilityListScreenProps = Record<string, never>;

export interface GemAbilityShowScreenProps {
  gem_ability_id: number;
}

export interface GemAbilityFormScreenProps {
  gem_ability_id: number | null;
}

export interface GemAbilityScreenPropsMap {
  [GemAbilityScreens.LIST]: GemAbilityListScreenProps;
  [GemAbilityScreens.SHOW]: GemAbilityShowScreenProps;
  [GemAbilityScreens.FORM]: GemAbilityFormScreenProps;
}

export type GemAbilityScreenName = keyof GemAbilityScreenPropsMap;
