import { ComponentType } from 'react';

import { ClassMasteryScreens } from './class-mastery-screen-constants';
import {
  ClassMasteryScreenName,
  ClassMasteryScreenPropsMap,
} from './class-mastery-screen-props';
import ClassMasteryFormScreen from '../screens/class-mastery-form-screen';
import ClassMasteryListScreen from '../screens/class-mastery-list-screen';
import ClassMasteryShowScreen from '../screens/class-mastery-show-screen';

export const classMasteryScreenRegistry: {
  [K in ClassMasteryScreenName]: ComponentType<ClassMasteryScreenPropsMap[K]>;
} = {
  [ClassMasteryScreens.LIST]: ClassMasteryListScreen,
  [ClassMasteryScreens.SHOW]: ClassMasteryShowScreen,
  [ClassMasteryScreens.FORM]: ClassMasteryFormScreen,
};
