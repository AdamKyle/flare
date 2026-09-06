import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { ClassScreenPropsMap } from './class-screen-props';
import { classScreenRegistry } from './class-screen-registry';
import ClassScreenProviderProps from './types/class-screen-provider-props';

const ClassScreenKit = createScreenManager<ClassScreenPropsMap>();

export const {
  ScreenManagerProvider: ClassScreenManagerProvider,
  useScreenNavigation: useClassScreenNavigation,
  ScreenHost: ClassScreenHost,
} = ClassScreenKit;

export const ClassScreenProvider = (props: ClassScreenProviderProps) => {
  return (
    <ClassScreenManagerProvider registry={classScreenRegistry}>
      {props.children}
    </ClassScreenManagerProvider>
  );
};
