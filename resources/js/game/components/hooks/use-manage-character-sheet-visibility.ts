import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseCharacterSheetVisibilityDefinition from './definitions/use-character-sheet-visibility-definition';
import { CharacterSheet } from '../character-sheet/event-types/character-sheet';

export const useManageCharacterSheetVisibility =
  (): UseCharacterSheetVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const manageCharacterSheetEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(CharacterSheet.OPEN_CHARACTER_SHEET);

    const openCharacterSheet = () => {
      manageCharacterSheetEmitter.emit(
        CharacterSheet.OPEN_CHARACTER_SHEET,
        true
      );
    };

    const closeCharacterSheet = () => {
      manageCharacterSheetEmitter.emit(
        CharacterSheet.OPEN_CHARACTER_SHEET,
        false
      );
    };

    return {
      openCharacterSheet,
      closeCharacterSheet,
    };
  };
