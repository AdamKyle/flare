import { ComponentType } from 'react';

import { MapGemScreens } from './map-gem-screen-constants';
import { MapGemScreenName, MapGemScreenPropsMap } from './map-gem-screen-props';
import MapGemFormScreen from '../screens/map-gem-form-screen';
import MapGemListScreen from '../screens/map-gem-list-screen';
import MapGemShowScreen from '../screens/map-gem-show-screen';

export const mapGemScreenRegistry: {
  [K in MapGemScreenName]: ComponentType<MapGemScreenPropsMap[K]>;
} = {
  [MapGemScreens.LIST]: MapGemListScreen,
  [MapGemScreens.SHOW]: MapGemShowScreen,
  [MapGemScreens.FORM]: MapGemFormScreen,
};
