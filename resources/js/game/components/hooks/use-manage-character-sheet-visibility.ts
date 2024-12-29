import { useEventSystem } from 'event-system/hooks/use-event-system';

import { CharacterSheet } from '../event-types/character-sheet';
import UseCharacterSheetVisibilityDefinition from './definitions/use-character-sheet-visibility-definition';
import { ActionCardEvents } from '../actions/partials/floating-cards/event-types/action-cards';

export const useManageCharacterSheetVisibility =
  (): UseCharacterSheetVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const closeCharacterSheetEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_CHARACTER_SHEET
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_CHARACTER_SHEET
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_CHARACTER_SHEET
        );

    const openCharacterSheet = () => {
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

      closeCharacterSheetEmitter.emit(
        CharacterSheet.OPEN_CHARACTER_SHEET,
        true
      );
    };

    const closeCharacterSheet = () => {
      closeCharacterSheetEmitter.emit(
        CharacterSheet.OPEN_CHARACTER_SHEET,
        false
      );
    };

    return {
      openCharacterSheet,
      closeCharacterSheet,
    };
  };
