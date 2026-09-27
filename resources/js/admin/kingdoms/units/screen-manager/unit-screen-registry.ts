import { ComponentType } from 'react';

import { UnitScreens } from './unit-screen-constants';
import { UnitScreenName, UnitScreenPropsMap } from './unit-screen-props';
import UnitFormScreen from '../screens/unit-form-screen';
import UnitListScreen from '../screens/unit-list-screen';
import UnitShowScreen from '../screens/unit-show-screen';

export const unitScreenRegistry: {
  [K in UnitScreenName]: ComponentType<UnitScreenPropsMap[K]>;
} = {
  [UnitScreens.UNIT_LIST]: UnitListScreen,
  [UnitScreens.UNIT_SHOW]: UnitShowScreen,
  [UnitScreens.UNIT_FORM]: UnitFormScreen,
};
