import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { GemAbilityScreenPropsMap } from './gem-ability-screen-props';
import { gemAbilityScreenRegistry } from './gem-ability-screen-registry';
import GemAbilityScreenProviderProps from './types/gem-ability-screen-provider-props';

const GemAbilityScreenKit = createScreenManager<GemAbilityScreenPropsMap>();

export const {
  ScreenManagerProvider: GemAbilityScreenManagerProvider,
  useScreenNavigation: useGemAbilityScreenNavigation,
  ScreenHost: GemAbilityScreenHost,
} = GemAbilityScreenKit;

export const GemAbilityScreenProvider = (
  props: GemAbilityScreenProviderProps
) => {
  return (
    <GemAbilityScreenManagerProvider registry={gemAbilityScreenRegistry}>
      {props.children}
    </GemAbilityScreenManagerProvider>
  );
};
