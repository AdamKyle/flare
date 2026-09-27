import { ComponentType } from 'react';

import { GuideQuestScreens } from './guide-quest-screen-constants';
import {
  GuideQuestScreenName,
  GuideQuestScreenPropsMap,
} from './guide-quest-screen-props';
import GuideQuestListScreen from '../screens/guide-quest-list-screen';
import GuideQuestShowScreen from '../screens/guide-quest-show-screen';

export const guideQuestScreenRegistry: {
  [K in GuideQuestScreenName]: ComponentType<GuideQuestScreenPropsMap[K]>;
} = {
  [GuideQuestScreens.LIST]: GuideQuestListScreen,
  [GuideQuestScreens.SHOW]: GuideQuestShowScreen,
};
