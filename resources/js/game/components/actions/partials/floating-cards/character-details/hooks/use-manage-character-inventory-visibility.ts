import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseManageCharacterInventoryVisibility from './definitions/use-manage-character-inventory-visibility';
import { CharacterSheet } from '../../../../../character-sheet/event-types/character-sheet';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageCharacterInventoryVisibility =
  (): UseManageCharacterInventoryVisibility => {
    const eventSystem = useEventSystem();

    const manageCharacterInventoryEmitter =
      eventSystem.fetchOrCreateEventEmitter<{ [key: string]: boolean }>(
        CharacterSheet.OPEN_INVENTORY_SECTION
      );

    const openCharacterInventory = () => {
      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);

      manageCharacterInventoryEmitter.emit(
        CharacterSheet.OPEN_INVENTORY_SECTION,
        true
      );
    };

    return {
      openCharacterInventory,
    };
  };
