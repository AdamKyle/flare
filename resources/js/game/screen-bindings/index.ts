import BindAnnouncementsSection from './bind-announcements-section';
import BindCharacterInventory from './bind-character-inventory';
import BindCharacterSheet from './bind-character-sheet';
import BindDonationsSection from './bind-donations-section';
import BindGoblinShop from './bind-goblin-shop';
import BindGuideQuestsSection from './bind-guide-quests-section';
import BindMonsterStatSection from './bind-monster-stat-section';
import BindShop from './bind-shop';

export const gameScreenBindings = [
  BindCharacterSheet,
  BindShop,
  BindGoblinShop,
  BindCharacterInventory,
  BindMonsterStatSection,
  BindAnnouncementsSection,
  BindDonationsSection,
  BindGuideQuestsSection,
] as const;
