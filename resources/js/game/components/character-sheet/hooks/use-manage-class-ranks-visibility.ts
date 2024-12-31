import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import useManageClassRanksVisibilityDefinition from './definitions/use-manage-class-ranks-visibility-definition';
import { CharacterSheet } from '../event-types/character-sheet';
import UseManageClassRanksVisibilityState from './types/use-manage-class-ranks-visibility-state';

export const useManageClassRanksVisibility =
  (): useManageClassRanksVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showClassRanks, setShowClassRanks] =
      useState<UseManageClassRanksVisibilityState['showClassRanks']>(false);

    const manageClassRanksEventEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_CLASS_RANKS_SYSTEM
    )
      ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_CLASS_RANKS_SYSTEM
        )
      : eventSystem.registerEvent<{ [key: string]: boolean }>(
          CharacterSheet.OPEN_CLASS_RANKS_SYSTEM
        );

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowClassRanks(visible);
      };

      manageClassRanksEventEmitter.on(
        CharacterSheet.OPEN_REINCARNATION_SYSTEM,
        updateVisibility
      );

      return () => {
        manageClassRanksEventEmitter.off(
          CharacterSheet.OPEN_REINCARNATION_SYSTEM,
          updateVisibility
        );
      };
    }, [manageClassRanksEventEmitter]);

    const openClassRanks = () => {
      manageClassRanksEventEmitter.emit(
        CharacterSheet.OPEN_REINCARNATION_SYSTEM,
        true
      );
    };

    const closeClassRanks = () => {
      manageClassRanksEventEmitter.emit(
        CharacterSheet.OPEN_REINCARNATION_SYSTEM,
        false
      );
    };

    return {
      showClassRanks,
      openClassRanks,
      closeClassRanks,
    };
  };
