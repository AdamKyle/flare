import { ComponentType } from 'react';

import { BuildingScreens } from './building-screen-constants';
import {
  BuildingScreenName,
  BuildingScreenPropsMap,
} from './building-screen-props';
import BuildingFormScreen from '../screens/building-form-screen';
import BuildingListScreen from '../screens/building-list-screen';
import BuildingShowScreen from '../screens/building-show-screen';

export const buildingScreenRegistry: {
  [K in BuildingScreenName]: ComponentType<BuildingScreenPropsMap[K]>;
} = {
  [BuildingScreens.BUILDING_LIST]: BuildingListScreen,
  [BuildingScreens.BUILDING_SHOW]: BuildingShowScreen,
  [BuildingScreens.BUILDING_FORM]: BuildingFormScreen,
};
