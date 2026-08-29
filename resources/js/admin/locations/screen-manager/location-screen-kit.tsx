import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { LocationScreenPropsMap } from './location-screen-props';
import { locationScreenRegistry } from './location-screen-registry';

const LocationScreenKit = createScreenManager<LocationScreenPropsMap>();

export const {
  ScreenManagerProvider: LocationScreenManagerProvider,
  useScreenNavigation: useLocationScreenNavigation,
  useBindScreen: useBindLocationScreen,
  ScreenHost: LocationScreenHost,
} = LocationScreenKit;

export const LocationScreenProvider = (props: {
  children?: React.ReactNode;
}) => {
  return (
    <LocationScreenManagerProvider registry={locationScreenRegistry}>
      {props.children}
    </LocationScreenManagerProvider>
  );
};
