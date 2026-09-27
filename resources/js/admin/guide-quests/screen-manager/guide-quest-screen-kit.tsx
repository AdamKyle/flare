import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { GuideQuestScreenPropsMap } from './guide-quest-screen-props';
import { guideQuestScreenRegistry } from './guide-quest-screen-registry';
import GuideQuestScreenProviderProps from './types/guide-quest-screen-provider-props';

const GuideQuestScreenKit = createScreenManager<GuideQuestScreenPropsMap>();

export const {
  ScreenManagerProvider: GuideQuestScreenManagerProvider,
  useScreenNavigation: useGuideQuestScreenNavigation,
  ScreenHost: GuideQuestScreenHost,
} = GuideQuestScreenKit;

export const GuideQuestScreenProvider = (
  props: GuideQuestScreenProviderProps
) => (
  <GuideQuestScreenManagerProvider registry={guideQuestScreenRegistry}>
    {props.children}
  </GuideQuestScreenManagerProvider>
);
