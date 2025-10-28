import BindCharacterInventory from './bind-character-inventory';
import BindCharacterSheet from './bind-character-sheet';
import BindGoblinShop from './bind-goblin-shop';
import BindShop from './bind-shop';

export const gameScreenBindings = [
  BindCharacterSheet,
  BindShop,
  BindGoblinShop,
  BindCharacterInventory,
] as const;
