import { ComponentType } from 'react';

import { RaceScreens } from './race-screen-constants';
import { RaceScreenName, RaceScreenPropsMap } from './race-screen-props';
import RaceFormScreen from '../screens/race-form-screen';
import RaceListScreen from '../screens/race-list-screen';
import RaceShowScreen from '../screens/race-show-screen';

export const raceScreenRegistry: {
  [K in RaceScreenName]: ComponentType<RaceScreenPropsMap[K]>;
} = {
  [RaceScreens.LIST]: RaceListScreen,
  [RaceScreens.SHOW]: RaceShowScreen,
  [RaceScreens.FORM]: RaceFormScreen,
};
