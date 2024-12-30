import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseManageCharacterInventoryVisibility from './definitions/use-manage-character-inventory-visibility';
import { CharacterSheet } from '../../../../../character-sheet/event-types/character-sheet';
import { ActionCardEvents } from '../../event-types/action-cards';

export const useManageCharacterInventoryVisibility =
  (): UseManageCharacterInventoryVisibility => {
    const eventSystem = useEventSystem();

    const manageCharacterInventoryEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_INVENTORY_SECTION
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_INVENTORY_SECTION
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_INVENTORY_SECTION
        );

    const openCharacterInventory = () => {
      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);
      const closeChatCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHAT_CARD);
      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHARACTER_CARD);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);
      closeChatCardEvent.emit(ActionCardEvents.OPEN_CHAT_CARD, false);
      closeCharacterCardEvent.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);

      manageCharacterInventoryEmitter.emit(
        CharacterSheet.OPEN_INVENTORY_SECTION,
        true
      );
    };

    return {
      openCharacterInventory,
    };
  };
