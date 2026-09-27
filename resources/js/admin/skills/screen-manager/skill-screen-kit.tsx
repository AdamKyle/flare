import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { SkillScreenPropsMap } from './skill-screen-props';
import { skillScreenRegistry } from './skill-screen-registry';
import SkillScreenProviderProps from './types/skill-screen-provider-props';

const SkillScreenKit = createScreenManager<SkillScreenPropsMap>();

export const {
  ScreenManagerProvider: SkillScreenManagerProvider,
  useScreenNavigation: useSkillScreenNavigation,
  ScreenHost: SkillScreenHost,
} = SkillScreenKit;

export const SkillScreenProvider = (props: SkillScreenProviderProps) => {
  return (
    <SkillScreenManagerProvider registry={skillScreenRegistry}>
      {props.children}
    </SkillScreenManagerProvider>
  );
};
