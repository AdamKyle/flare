import { ComponentType } from 'react';

import { GemAbilityScreens } from './gem-ability-screen-constants';
import {
  GemAbilityScreenName,
  GemAbilityScreenPropsMap,
} from './gem-ability-screen-props';
import GemAbilityFormScreen from '../screens/gem-ability-form-screen';
import GemAbilityListScreen from '../screens/gem-ability-list-screen';
import GemAbilityShowScreen from '../screens/gem-ability-show-screen';

export const gemAbilityScreenRegistry: {
  [K in GemAbilityScreenName]: ComponentType<GemAbilityScreenPropsMap[K]>;
} = {
  [GemAbilityScreens.LIST]: GemAbilityListScreen,
  [GemAbilityScreens.SHOW]: GemAbilityShowScreen,
  [GemAbilityScreens.FORM]: GemAbilityFormScreen,
};
