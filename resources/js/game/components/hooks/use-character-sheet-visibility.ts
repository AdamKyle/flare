import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useState, useEffect } from 'react';

import UseCharacterSheetVisibilityState from './types/use-character-sheet-visibility-state';
import { CharacterSheet } from '../character-sheet/event-types/character-sheet';

export const useCharacterSheetVisibility =
  (): UseCharacterSheetVisibilityState => {
    const eventSystem = useEventSystem();

    const [showCharacterSheet, setShowCharacterSheet] =
      useState<UseCharacterSheetVisibilityState['showCharacterSheet']>(false);

    const characterSheetVisibility = eventSystem.getEventEmitter<{
      [key: string]: boolean;
    }>(CharacterSheet.OPEN_CHARACTER_SHEET);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowCharacterSheet(visible);
      };

      characterSheetVisibility.on(
        CharacterSheet.OPEN_CHARACTER_SHEET,
        updateVisibility
      );

      return () => {
        characterSheetVisibility.off(
          CharacterSheet.OPEN_CHARACTER_SHEET,
          updateVisibility
        );
      };
    }, [characterSheetVisibility]);

    return { showCharacterSheet };
  };
