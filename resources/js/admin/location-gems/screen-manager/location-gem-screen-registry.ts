import { ComponentType } from 'react';

import { LocationGemScreens } from './location-gem-screen-constants';
import {
  LocationGemScreenName,
  LocationGemScreenPropsMap,
} from './location-gem-screen-props';
import LocationGemFormScreen from '../screens/location-gem-form-screen';
import LocationGemListScreen from '../screens/location-gem-list-screen';
import LocationGemShowScreen from '../screens/location-gem-show-screen';

export const locationGemScreenRegistry: {
  [K in LocationGemScreenName]: ComponentType<LocationGemScreenPropsMap[K]>;
} = {
  [LocationGemScreens.LIST]: LocationGemListScreen,
  [LocationGemScreens.SHOW]: LocationGemShowScreen,
  [LocationGemScreens.FORM]: LocationGemFormScreen,
};
