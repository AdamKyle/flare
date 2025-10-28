import React from 'react';

import { AppScreenPropsMap } from './screen-manager-props';
import { appScreenRegistry } from './screen-manager-registry';
import createScreenManager from '../../screen-manager/create-screen-manager';

const ScreenKit = createScreenManager<AppScreenPropsMap>();

export const {
  ScreenManagerProvider,
  useScreenNavigation,
  useBindScreen,
  ScreenHost,
} = ScreenKit;

export const AppScreenProvider = (props: { children?: React.ReactNode }) => {
  return (
    <ScreenManagerProvider registry={appScreenRegistry}>
      {props.children}
    </ScreenManagerProvider>
  );
};
