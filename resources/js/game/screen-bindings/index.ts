import BindAnnouncementDetailsSection from './announcement-bindings/bind-announcement-details-section';
import BindAnnouncementsSection from './announcement-bindings/bind-announcements-section';
import BindCharacterAttackDetails from './character-bindings/bind-character-attack-details';
import BindCharacterInventory from './character-bindings/bind-character-inventory';
import BindCharacterSheet from './character-bindings/bind-character-sheet';
import BindCharacterStatDetails from './character-bindings/bind-character-stat-details';
import BindDonationsSection from './donations-bindings/bind-donations-section';
import BindGuideQuestsSection from './guide-quest-bindings/bind-guide-quests-section';
import BindMonsterStatSection from './monster-bindings/bind-monster-stat-section';
import BindGoblinShop from './shop-bindings/bind-goblin-shop';
import BindShop from './shop-bindings/bind-shop';

export const gameScreenBindings = [
  BindCharacterSheet,
  BindShop,
  BindGoblinShop,
  BindCharacterInventory,
  BindCharacterStatDetails,
  BindCharacterAttackDetails,
  BindMonsterStatSection,
  BindAnnouncementsSection,
  BindAnnouncementDetailsSection,
  BindDonationsSection,
  BindGuideQuestsSection,
] as const;
