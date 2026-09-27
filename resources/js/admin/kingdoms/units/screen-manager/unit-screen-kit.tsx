import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import UnitScreenProviderProps from './types/unit-screen-provider-props';
import { UnitScreenPropsMap } from './unit-screen-props';
import { unitScreenRegistry } from './unit-screen-registry';

const UnitScreenKit = createScreenManager<UnitScreenPropsMap>();

export const {
  ScreenManagerProvider: UnitScreenManagerProvider,
  useScreenNavigation: useUnitScreenNavigation,
  ScreenHost: UnitScreenHost,
} = UnitScreenKit;

export const UnitScreenProvider = (props: UnitScreenProviderProps) => {
  return (
    <UnitScreenManagerProvider registry={unitScreenRegistry}>
      {props.children}
    </UnitScreenManagerProvider>
  );
};
