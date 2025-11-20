import { ComponentType } from 'react';

import { Screens } from './screen-manager-constants';
import { AppScreenPropsMap, AppScreenName } from './screen-manager-props';
import { MonsterStatSection } from '../../game/components/actions/partials/monster-stat-section/monster-stat-section';
import Announcements from '../../game/components/announcements/announcements';
import CharacterSheet from '../../game/components/character-sheet/character-sheet';
import GoblinShopScreen from '../../game/components/goblin-shop/goblin-shop-screen';
import CharacterInventory from '../../game/components/partials/character-inventory';
import ShopScreen from '../../game/components/shop/shop-screen';

export const appScreenRegistry: {
  [K in AppScreenName]: ComponentType<AppScreenPropsMap[K]>;
} = {
  [Screens.CHARACTER_SHEET]: CharacterSheet,
  [Screens.SHOP]: ShopScreen,
  [Screens.GOBLIN_SHOP]: GoblinShopScreen,
  [Screens.CHARACTER_INVENTORY]: CharacterInventory,
  [Screens.MONSTER_DETAILS]: MonsterStatSection,
  [Screens.ANNOUNCEMENTS]: Announcements,
};
