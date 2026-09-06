import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { ClassMasteryScreenPropsMap } from './class-mastery-screen-props';
import { classMasteryScreenRegistry } from './class-mastery-screen-registry';
import ClassMasteryScreenProviderProps from './types/class-mastery-screen-provider-props';

const ClassMasteryScreenKit = createScreenManager<ClassMasteryScreenPropsMap>();

export const {
  ScreenManagerProvider: ClassMasteryScreenManagerProvider,
  useScreenNavigation: useClassMasteryScreenNavigation,
  ScreenHost: ClassMasteryScreenHost,
} = ClassMasteryScreenKit;

export const ClassMasteryScreenProvider = (
  props: ClassMasteryScreenProviderProps
) => {
  return (
    <ClassMasteryScreenManagerProvider registry={classMasteryScreenRegistry}>
      {props.children}
    </ClassMasteryScreenManagerProvider>
  );
};
