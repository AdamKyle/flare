import { useEventSystem } from 'event-system/hooks/use-event-system';

import { CharacterInventory } from '../event-types/character-inventory';
import UseCharacterGemBagDefinition from './definition/use-character-gem-bag-definition';

export const useOpenCharacterGemBag = (): UseCharacterGemBagDefinition => {
  const eventSystem = useEventSystem();

  const manageCharacterGemBag = eventSystem.isEventRegistered(
    CharacterInventory.OPEN_GEM_BAG
  )
    ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
        CharacterInventory.OPEN_GEM_BAG
      )
    : eventSystem.registerEvent<{ [key: string]: boolean }>(
        CharacterInventory.OPEN_GEM_BAG
      );

  const openGemBag = () => {
    manageCharacterGemBag.emit(CharacterInventory.OPEN_GEM_BAG, true);
  };

  return {
    openGemBag,
  };
};
