import { ComponentType } from 'react';

import { NpcScreens } from './npc-screen-constants';
import { NpcScreenName, NpcScreenPropsMap } from './npc-screen-props';
import NpcFormEntryScreen from '../screens/npc-form-entry-screen';
import NpcListScreen from '../screens/npc-list-screen';
import NpcShowScreen from '../screens/npc-show-screen';

export const npcScreenRegistry: {
  [K in NpcScreenName]: ComponentType<NpcScreenPropsMap[K]>;
} = {
  [NpcScreens.LIST]: NpcListScreen,
  [NpcScreens.SHOW]: NpcShowScreen,
  [NpcScreens.FORM]: NpcFormEntryScreen,
};
