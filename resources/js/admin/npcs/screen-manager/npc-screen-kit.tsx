import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { NpcScreenPropsMap } from './npc-screen-props';
import { npcScreenRegistry } from './npc-screen-registry';

const NpcScreenKit = createScreenManager<NpcScreenPropsMap>();

export const {
  ScreenManagerProvider: NpcScreenManagerProvider,
  useScreenNavigation: useNpcScreenNavigation,
  useBindScreen: useBindNpcScreen,
  ScreenHost: NpcScreenHost,
} = NpcScreenKit;

export const NpcScreenProvider = (props: { children?: React.ReactNode }) => {
  return (
    <NpcScreenManagerProvider registry={npcScreenRegistry}>
      {props.children}
    </NpcScreenManagerProvider>
  );
};
