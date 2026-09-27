import { ComponentType } from 'react';

import { SkillScreens } from './skill-screen-constants';
import { SkillScreenName, SkillScreenPropsMap } from './skill-screen-props';
import SkillFormScreen from '../screens/skill-form-screen';
import SkillListScreen from '../screens/skill-list-screen';
import SkillShowScreen from '../screens/skill-show-screen';

export const skillScreenRegistry: {
  [K in SkillScreenName]: ComponentType<SkillScreenPropsMap[K]>;
} = {
  [SkillScreens.LIST]: SkillListScreen,
  [SkillScreens.SHOW]: SkillShowScreen,
  [SkillScreens.FORM]: SkillFormScreen,
};
