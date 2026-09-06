import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { MapGemScreenPropsMap } from './map-gem-screen-props';
import { mapGemScreenRegistry } from './map-gem-screen-registry';
import MapGemScreenProviderProps from './types/map-gem-screen-provider-props';

const MapGemScreenKit = createScreenManager<MapGemScreenPropsMap>();

export const {
  ScreenManagerProvider: MapGemScreenManagerProvider,
  useScreenNavigation: useMapGemScreenNavigation,
  ScreenHost: MapGemScreenHost,
} = MapGemScreenKit;

export const MapGemScreenProvider = (props: MapGemScreenProviderProps) => {
  return (
    <MapGemScreenManagerProvider registry={mapGemScreenRegistry}>
      {props.children}
    </MapGemScreenManagerProvider>
  );
};
