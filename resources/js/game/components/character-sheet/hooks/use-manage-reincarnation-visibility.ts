import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageReincarnationVisibilityDefinition from './definitions/use-manage-reincarnation-visibility-definition';
import { CharacterSheet } from '../event-types/character-sheet';
import UseManageCharacterReincarnationVisibilityState from './types/use-manage-character-reincarnation-visibility-state';

export const useManageReincarnationVisibility =
  (): UseManageReincarnationVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showReincarnation, setShowReincarnation] =
      useState<
        UseManageCharacterReincarnationVisibilityState['showReincarnation']
      >(false);

    const manageReincarnationEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_REINCARNATION_SYSTEM
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_REINCARNATION_SYSTEM
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_REINCARNATION_SYSTEM
        );

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowReincarnation(visible);
      };

      manageReincarnationEmitter.on(
        CharacterSheet.OPEN_REINCARNATION_SYSTEM,
        updateVisibility
      );

      return () => {
        manageReincarnationEmitter.off(
          CharacterSheet.OPEN_REINCARNATION_SYSTEM,
          updateVisibility
        );
      };
    }, [manageReincarnationEmitter]);

    const openReincarnation = () => {
      manageReincarnationEmitter.emit(
        CharacterSheet.OPEN_REINCARNATION_SYSTEM,
        true
      );
    };

    const closeReincarnation = () => {
      manageReincarnationEmitter.emit(
        CharacterSheet.OPEN_REINCARNATION_SYSTEM,
        false
      );
    };

    return {
      showReincarnation,
      openReincarnation,
      closeReincarnation,
    };
  };
