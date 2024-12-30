import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseCharacterInventoryVisibility from './definitions/use-character-inventory-visibility';
import UseCharacterInventoryVisibilityState from './types/use-character-inventory-visibility-state';
import { CharacterSheet } from '../character-sheet/event-types/character-sheet';

export const useCharacterInventoryVisibility =
  (): UseCharacterInventoryVisibility => {
    const eventSystem = useEventSystem();

    const [showCharacterInventory, setShowCharacterInventory] =
      useState<UseCharacterInventoryVisibilityState['showCharacterInventory']>(
        false
      );

    const characterInventoryVisibility = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_INVENTORY_SECTION
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_INVENTORY_SECTION
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_INVENTORY_SECTION
        );

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowCharacterInventory(visible);
      };

      characterInventoryVisibility.on(
        CharacterSheet.OPEN_INVENTORY_SECTION,
        updateVisibility
      );

      return () => {
        characterInventoryVisibility.off(
          CharacterSheet.OPEN_INVENTORY_SECTION,
          updateVisibility
        );
      };
    }, [characterInventoryVisibility]);

    const closeInventory = () => {
      characterInventoryVisibility.emit(
        CharacterSheet.OPEN_INVENTORY_SECTION,
        false
      );
    };

    return { showCharacterInventory, closeInventory };
  };
