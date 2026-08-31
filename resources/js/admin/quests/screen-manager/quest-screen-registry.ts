import { ComponentType } from 'react';

import { QuestScreens } from './quest-screen-constants';
import { QuestScreenName, QuestScreenPropsMap } from './quest-screen-props';
import QuestFormScreen from '../screens/quest-form-screen';
import QuestListScreen from '../screens/quest-list-screen';
import QuestShowScreen from '../screens/quest-show-screen';

export const questScreenRegistry: {
  [K in QuestScreenName]: ComponentType<QuestScreenPropsMap[K]>;
} = {
  [QuestScreens.LIST]: QuestListScreen,
  [QuestScreens.SHOW]: QuestShowScreen,
  [QuestScreens.FORM]: QuestFormScreen,
};
