import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { QuestScreenPropsMap } from './quest-screen-props';
import { questScreenRegistry } from './quest-screen-registry';

const QuestScreenKit = createScreenManager<QuestScreenPropsMap>();

export const {
  ScreenManagerProvider: QuestScreenManagerProvider,
  useScreenNavigation: useQuestScreenNavigation,
  ScreenHost: QuestScreenHost,
} = QuestScreenKit;

export const QuestScreenProvider = (props: { children?: React.ReactNode }) => {
  return (
    <QuestScreenManagerProvider registry={questScreenRegistry}>
      {props.children}
    </QuestScreenManagerProvider>
  );
};
