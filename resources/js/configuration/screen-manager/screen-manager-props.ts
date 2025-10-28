import { Screens } from './screen-manager-constants';
import MonsterStatSectionProps from '../../game/components/actions/partials/monster-stat-section/types/monster-stat-section-props';
import CharacterSheetProps from '../../game/components/character-sheet/types/character-sheet-props';
import GoblinShopProps from '../../game/components/goblin-shop/types/goblin-shop-props';
import CharacterInventoryProps from '../../game/components/partials/types/character-inventory-props';
import ShopScreenProps from '../../game/components/shop/types/shop-screen-props';

export interface AppScreenPropsMap {
  [Screens.CHARACTER_SHEET]: CharacterSheetProps;
  [Screens.SHOP]: ShopScreenProps;
  [Screens.GOBLIN_SHOP]: GoblinShopProps;
  [Screens.CHARACTER_INVENTORY]: CharacterInventoryProps;
  [Screens.MONSTER_DETAILS]: MonsterStatSectionProps;
}

export type AppScreenName = keyof AppScreenPropsMap;
export type AppScreenPropsOf<K extends AppScreenName> = AppScreenPropsMap[K];

export type ScreenName = AppScreenName;
export type ScreenPropsOf<K extends ScreenName> = AppScreenPropsOf<K>;
