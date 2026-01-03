import { ComponentType } from 'react';

import { Screens } from './screen-manager-constants';
import { AppScreenPropsMap, AppScreenName } from './screen-manager-props';
import { MonsterStatSection } from '../../game/components/actions/partials/monster-stat-section/monster-stat-section';
import AnnouncementDetails from '../../game/components/announcements/announcement-details';
import Announcements from '../../game/components/announcements/announcements';
import CharacterSheet from '../../game/components/character-sheet/character-sheet';
import CharacterAttackTypeBreakdown from '../../game/components/character-sheet/partials/character-attack-details/character-attack-type-breakdown';
import CharacterStatTypeBreakDown from '../../game/components/character-sheet/partials/character-stat-types/character-stat-type-breakdown';
import Donations from '../../game/components/donations/dontations';
import GoblinShopScreen from '../../game/components/goblin-shop/goblin-shop-screen';
import GuideQuest from '../../game/components/guide-quests/guide-quest';
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
  [Screens.DONATIONS]: Donations,
  [Screens.GUIDE_QUESTS]: GuideQuest,
  [Screens.ANNOUNCEMENT_DETAILS]: AnnouncementDetails,
  [Screens.CHARACTER_STAT_DETAILS]: CharacterStatTypeBreakDown,
  [Screens.CHARACTER_ATTACK_DETAILS]: CharacterAttackTypeBreakdown,
};
