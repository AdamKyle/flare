import { ComponentType } from 'react';

import { MonsterScreens } from './monster-screen-constants';
import {
  MonsterScreenName,
  MonsterScreenPropsMap,
} from './monster-screen-props';
import MonsterFormScreen from '../screens/monster-form-screen';
import MonsterListScreen from '../screens/monster-list-screen';
import MonsterShowScreen from '../screens/monster-show-screen';

export const monsterScreenRegistry: {
  [K in MonsterScreenName]: ComponentType<MonsterScreenPropsMap[K]>;
} = {
  [MonsterScreens.LIST]: MonsterListScreen,
  [MonsterScreens.SHOW]: MonsterShowScreen,
  [MonsterScreens.FORM]: MonsterFormScreen,
};
