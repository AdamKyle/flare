import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { ItemScreenPropsMap } from './item-screen-props';
import { itemScreenRegistry } from './item-screen-registry';

const ItemScreenKit = createScreenManager<ItemScreenPropsMap>();

export const {
  ScreenManagerProvider: ItemScreenManagerProvider,
  useScreenNavigation: useItemScreenNavigation,
  ScreenHost: ItemScreenHost,
} = ItemScreenKit;

export const ItemScreenProvider = (props: { children?: React.ReactNode }) => {
  return (
    <ItemScreenManagerProvider registry={itemScreenRegistry}>
      {props.children}
    </ItemScreenManagerProvider>
  );
};
