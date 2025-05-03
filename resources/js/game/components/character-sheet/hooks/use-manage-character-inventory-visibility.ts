import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import useManageCharacterInventoryVisibilityDefinition from './definitions/use-manage-character-inventory-visibility-definition';
import { CharacterSheet } from '../event-types/character-sheet';

export const useManageCharacterInventoryVisibility =
  (): useManageCharacterInventoryVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showInventory, setShowInventory] = useState<boolean>(false);

    const manageInventoryEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(CharacterSheet.OPEN_INVENTORY_SECTION);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowInventory(visible);
      };

      manageInventoryEmitter.on(
        CharacterSheet.OPEN_INVENTORY_SECTION,
        updateVisibility
      );

      return () => {
        manageInventoryEmitter.off(
          CharacterSheet.OPEN_INVENTORY_SECTION,
          updateVisibility
        );
      };
    }, [manageInventoryEmitter]);

    const openInventory = () => {
      manageInventoryEmitter.emit(CharacterSheet.OPEN_INVENTORY_SECTION, true);
    };

    const closeInventory = () => {
      manageInventoryEmitter.emit(CharacterSheet.OPEN_INVENTORY_SECTION, false);
    };

    return {
      showInventory,
      openInventory,
      closeInventory,
    };
  };
