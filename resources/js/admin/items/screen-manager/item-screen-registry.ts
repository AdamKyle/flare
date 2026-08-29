import { ComponentType } from 'react';

import { ItemScreens } from './item-screen-constants';
import { ItemScreenName, ItemScreenPropsMap } from './item-screen-props';
import ItemFormScreen from '../screens/item-form-screen';
import ItemListScreen from '../screens/item-list-screen';
import ItemShowScreen from '../screens/item-show-screen';

export const itemScreenRegistry: {
  [K in ItemScreenName]: ComponentType<ItemScreenPropsMap[K]>;
} = {
  [ItemScreens.LIST]: ItemListScreen,
  [ItemScreens.SHOW]: ItemShowScreen,
  [ItemScreens.FORM]: ItemFormScreen,
};
