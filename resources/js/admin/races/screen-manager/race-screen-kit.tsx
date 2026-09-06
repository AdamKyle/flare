import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { RaceScreenPropsMap } from './race-screen-props';
import { raceScreenRegistry } from './race-screen-registry';
import RaceScreenProviderProps from './types/race-screen-provider-props';

const RaceScreenKit = createScreenManager<RaceScreenPropsMap>();

export const {
  ScreenManagerProvider: RaceScreenManagerProvider,
  useScreenNavigation: useRaceScreenNavigation,
  ScreenHost: RaceScreenHost,
} = RaceScreenKit;

export const RaceScreenProvider = (props: RaceScreenProviderProps) => {
  return (
    <RaceScreenManagerProvider registry={raceScreenRegistry}>
      {props.children}
    </RaceScreenManagerProvider>
  );
};
