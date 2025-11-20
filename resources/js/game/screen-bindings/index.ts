import BindAnnouncementsSection from './bind-announcements-section';
import BindCharacterInventory from './bind-character-inventory';
import BindCharacterSheet from './bind-character-sheet';
import BindGoblinShop from './bind-goblin-shop';
import BindMonsterStatSection from './bind-monster-stat-section';
import BindShop from './bind-shop';

export const gameScreenBindings = [
  BindCharacterSheet,
  BindShop,
  BindGoblinShop,
  BindCharacterInventory,
  BindMonsterStatSection,
  BindAnnouncementsSection,
] as const;
