import { useEventSystem } from 'event-system/hooks/use-event-system';

import { CharacterInventory } from '../event-types/character-inventory';
import UseOpenCharacterBackpackDefinition from './definition/use-open-character-backpack-definition';

export const useOpenCharacterBackpack =
  (): UseOpenCharacterBackpackDefinition => {
    const eventSystem = useEventSystem();

    const manageCharacterBackpack = eventSystem.isEventRegistered(
      CharacterInventory.OPEN_BACKPACK
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_BACKPACK
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_BACKPACK
        );

    const openBackpack = () => {
      manageCharacterBackpack.emit(CharacterInventory.OPEN_BACKPACK, true);
    };

    return {
      openBackpack,
    };
  };
