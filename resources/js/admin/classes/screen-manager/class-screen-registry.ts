import { ComponentType } from 'react';

import { ClassScreens } from './class-screen-constants';
import { ClassScreenName, ClassScreenPropsMap } from './class-screen-props';
import ClassFormScreen from '../screens/class-form-screen';
import ClassListScreen from '../screens/class-list-screen';
import ClassShowScreen from '../screens/class-show-screen';

export const classScreenRegistry: {
  [K in ClassScreenName]: ComponentType<ClassScreenPropsMap[K]>;
} = {
  [ClassScreens.LIST]: ClassListScreen,
  [ClassScreens.SHOW]: ClassShowScreen,
  [ClassScreens.FORM]: ClassFormScreen,
};
