import { Screens } from './screen-manager-constants';
import MonsterStatSectionProps from '../../game/components/actions/partials/monster-stat-section/types/monster-stat-section-props';
import AnnouncementDetailsProps from '../../game/components/announcements/types/announcement-details-props';
import AnnouncementsProps from '../../game/components/announcements/types/announcements-props';
import CharacterStatTypeBreakdownProps from '../../game/components/character-sheet/partials/character-stat-types/types/character-stat-type-breakdown-props';
import CharacterSheetProps from '../../game/components/character-sheet/types/character-sheet-props';
import DonationsProps from '../../game/components/donations/types/donations-props';
import GoblinShopProps from '../../game/components/goblin-shop/types/goblin-shop-props';
import GuideQuestProps from '../../game/components/guide-quests/types/guide-quest-props';
import CharacterAttackTypeBreakdownProps from '../../game/components/partials/types/character-attack-type-breakdown-props';
import CharacterInventoryProps from '../../game/components/partials/types/character-inventory-props';
import ShopScreenProps from '../../game/components/shop/types/shop-screen-props';

export interface AppScreenPropsMap {
  [Screens.CHARACTER_SHEET]: CharacterSheetProps;
  [Screens.SHOP]: ShopScreenProps;
  [Screens.GOBLIN_SHOP]: GoblinShopProps;
  [Screens.CHARACTER_INVENTORY]: CharacterInventoryProps;
  [Screens.MONSTER_DETAILS]: MonsterStatSectionProps;
  [Screens.ANNOUNCEMENTS]: AnnouncementsProps;
  [Screens.DONATIONS]: DonationsProps;
  [Screens.GUIDE_QUESTS]: GuideQuestProps;
  [Screens.ANNOUNCEMENT_DETAILS]: AnnouncementDetailsProps;
  [Screens.CHARACTER_STAT_DETAILS]: CharacterStatTypeBreakdownProps;
  [Screens.CHARACTER_ATTACK_DETAILS]: CharacterAttackTypeBreakdownProps;
}

export type AppScreenName = keyof AppScreenPropsMap;
export type AppScreenPropsOf<K extends AppScreenName> = AppScreenPropsMap[K];

export type ScreenName = AppScreenName;
export type ScreenPropsOf<K extends ScreenName> = AppScreenPropsOf<K>;
