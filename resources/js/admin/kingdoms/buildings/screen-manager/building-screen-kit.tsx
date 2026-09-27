import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { BuildingScreenPropsMap } from './building-screen-props';
import { buildingScreenRegistry } from './building-screen-registry';
import BuildingScreenProviderProps from './types/building-screen-provider-props';

const BuildingScreenKit = createScreenManager<BuildingScreenPropsMap>();

export const {
  ScreenManagerProvider: BuildingScreenManagerProvider,
  useScreenNavigation: useBuildingScreenNavigation,
  ScreenHost: BuildingScreenHost,
} = BuildingScreenKit;

export const BuildingScreenProvider = (props: BuildingScreenProviderProps) => {
  return (
    <BuildingScreenManagerProvider registry={buildingScreenRegistry}>
      {props.children}
    </BuildingScreenManagerProvider>
  );
};
