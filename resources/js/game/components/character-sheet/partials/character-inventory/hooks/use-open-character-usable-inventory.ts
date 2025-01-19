import { useEventSystem } from 'event-system/hooks/use-event-system';

import { CharacterInventory } from '../event-types/character-inventory';
import UseOpenCharacterUsableInventoryDefinition from './definition/use-open-character-usable-inventory-definition';

export const useOpenCharacterUsableInventory =
  (): UseOpenCharacterUsableInventoryDefinition => {
    const eventSystem = useEventSystem();

    const manageCharacterUsableInventory = eventSystem.isEventRegistered(
      CharacterInventory.OPEN_USABLE_INVENTORY
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_USABLE_INVENTORY
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_USABLE_INVENTORY
        );

    const openUsableInventory = () => {
      manageCharacterUsableInventory.emit(
        CharacterInventory.OPEN_USABLE_INVENTORY,
        true
      );
    };

    return {
      openUsableInventory,
    };
  };
