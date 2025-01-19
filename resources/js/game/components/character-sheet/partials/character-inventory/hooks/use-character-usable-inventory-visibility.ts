import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { CharacterInventory } from '../event-types/character-inventory';
import UseCharacterUsableInventoryVisibilityDefinition from './definition/use-character-usable-inventory-visibility-definition';
import { UseCharacterUsableInventoryVisibilityState } from './state/use-character-usable-inventory-visibility-state';

export const useCharacterUsableInventoryVisibility =
  (): UseCharacterUsableInventoryVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showUsableInventory, setShowUsableInventory] =
      useState<
        UseCharacterUsableInventoryVisibilityState['showUsableInventory']
      >(false);

    const manageCharacterUsableInventory = eventSystem.isEventRegistered(
      CharacterInventory.OPEN_USABLE_INVENTORY
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_USABLE_INVENTORY
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterInventory.OPEN_USABLE_INVENTORY
        );

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowUsableInventory(visible);
      };

      manageCharacterUsableInventory.on(
        CharacterInventory.OPEN_USABLE_INVENTORY,
        updateVisibility
      );

      return () => {
        manageCharacterUsableInventory.off(
          CharacterInventory.OPEN_USABLE_INVENTORY,
          updateVisibility
        );
      };
    }, [manageCharacterUsableInventory]);

    const closeUsableInventory = () => {
      manageCharacterUsableInventory.emit(
        CharacterInventory.OPEN_USABLE_INVENTORY,
        false
      );
    };

    return {
      showUsableInventory,
      closeUsableInventory,
    };
  };
