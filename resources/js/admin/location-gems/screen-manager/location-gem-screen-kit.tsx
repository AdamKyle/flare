import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { LocationGemScreenPropsMap } from './location-gem-screen-props';
import { locationGemScreenRegistry } from './location-gem-screen-registry';
import LocationGemScreenProviderProps from './types/location-gem-screen-provider-props';

const LocationGemScreenKit = createScreenManager<LocationGemScreenPropsMap>();

export const {
  ScreenManagerProvider: LocationGemScreenManagerProvider,
  useScreenNavigation: useLocationGemScreenNavigation,
  ScreenHost: LocationGemScreenHost,
} = LocationGemScreenKit;

export const LocationGemScreenProvider = (
  props: LocationGemScreenProviderProps
) => {
  return (
    <LocationGemScreenManagerProvider registry={locationGemScreenRegistry}>
      {props.children}
    </LocationGemScreenManagerProvider>
  );
};
