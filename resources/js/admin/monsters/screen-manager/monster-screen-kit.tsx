import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { MonsterScreenPropsMap } from './monster-screen-props';
import { monsterScreenRegistry } from './monster-screen-registry';

const MonsterScreenKit = createScreenManager<MonsterScreenPropsMap>();

export const {
  ScreenManagerProvider: MonsterScreenManagerProvider,
  useScreenNavigation: useMonsterScreenNavigation,
  ScreenHost: MonsterScreenHost,
} = MonsterScreenKit;

export const MonsterScreenProvider = (props: {
  children?: React.ReactNode;
}) => {
  return (
    <MonsterScreenManagerProvider registry={monsterScreenRegistry}>
      {props.children}
    </MonsterScreenManagerProvider>
  );
};
