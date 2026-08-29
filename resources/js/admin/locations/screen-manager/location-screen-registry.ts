import { ComponentType } from 'react';

import { LocationScreens } from './location-screen-constants';
import {
  LocationScreenName,
  LocationScreenPropsMap,
} from './location-screen-props';
import LocationFormEntryScreen from '../screens/location-form-entry-screen';
import LocationListScreen from '../screens/location-list-screen';
import LocationShowScreen from '../screens/location-show-screen';

export const locationScreenRegistry: {
  [K in LocationScreenName]: ComponentType<LocationScreenPropsMap[K]>;
} = {
  [LocationScreens.LIST]: LocationListScreen,
  [LocationScreens.SHOW]: LocationShowScreen,
  [LocationScreens.FORM]: LocationFormEntryScreen,
};
