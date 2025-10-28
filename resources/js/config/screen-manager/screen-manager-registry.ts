import { ComponentType } from 'react';

import { Screens } from './screen-manager-constants';
import { AppScreenPropsMap, AppScreenName } from './screen-manager-props';
import CharacterSheet from '../../game/components/character-sheet/character-sheet';
import GoblinShopScreen from '../../game/components/goblin-shop/goblin-shop-screen';
import ShopScreen from '../../game/components/shop/shop-screen';

export const appScreenRegistry: {
  [K in AppScreenName]: ComponentType<AppScreenPropsMap[K]>;
} = {
  [Screens.CHARACTER_SHEET]: CharacterSheet,
  [Screens.SHOP]: ShopScreen,
  [Screens.GOBLIN_SHOP]: GoblinShopScreen,
};
