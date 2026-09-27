import { ComponentType } from 'react';

import { PassiveSkillScreens } from './passive-skill-screen-constants';
import {
  PassiveSkillScreenName,
  PassiveSkillScreenPropsMap,
} from './passive-skill-screen-props';
import PassiveSkillFormScreen from '../screens/passive-skill-form-screen';
import PassiveSkillListScreen from '../screens/passive-skill-list-screen';
import PassiveSkillShowScreen from '../screens/passive-skill-show-screen';

export const passiveSkillScreenRegistry: {
  [K in PassiveSkillScreenName]: ComponentType<PassiveSkillScreenPropsMap[K]>;
} = {
  [PassiveSkillScreens.LIST]: PassiveSkillListScreen,
  [PassiveSkillScreens.SHOW]: PassiveSkillShowScreen,
  [PassiveSkillScreens.FORM]: PassiveSkillFormScreen,
};
