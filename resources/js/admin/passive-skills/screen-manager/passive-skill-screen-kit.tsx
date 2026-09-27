import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { PassiveSkillScreenPropsMap } from './passive-skill-screen-props';
import { passiveSkillScreenRegistry } from './passive-skill-screen-registry';
import PassiveSkillScreenProviderProps from './types/passive-skill-screen-provider-props';

const PassiveSkillScreenKit = createScreenManager<PassiveSkillScreenPropsMap>();

export const {
  ScreenManagerProvider: PassiveSkillScreenManagerProvider,
  useScreenNavigation: usePassiveSkillScreenNavigation,
  ScreenHost: PassiveSkillScreenHost,
} = PassiveSkillScreenKit;

export const PassiveSkillScreenProvider = (
  props: PassiveSkillScreenProviderProps
) => {
  return (
    <PassiveSkillScreenManagerProvider registry={passiveSkillScreenRegistry}>
      {props.children}
    </PassiveSkillScreenManagerProvider>
  );
};
