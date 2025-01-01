import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { StatTypes } from '../enums/stat-types';
import { CharacterSheet } from '../event-types/character-sheet';
import UseStatDefinitionVisibilityDefinition from './definitions/use-stat-definition-visibility-definition';
import UseStatDetailsVisibilityState from './types/use-stat-details-visibility-state';

export const useStatDetailsVisibility =
  (): UseStatDefinitionVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showStatDetails, setShowStatDetails] =
      useState<UseStatDetailsVisibilityState['showStateDetails']>(false);

    const [statType, setStatType] =
      useState<UseStatDetailsVisibilityState['statType']>(null);

    const manageStatDetailsEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_STAT_DETAILS
    )
      ? eventSystem.getEventEmitter<{ [key: string]: [boolean, StatTypes?] }>(
          CharacterSheet.OPEN_STAT_DETAILS
        )
      : eventSystem.registerEvent<{ [key: string]: [boolean, StatTypes?] }>(
          CharacterSheet.OPEN_STAT_DETAILS
        );

    useEffect(() => {
      const updateVisibility = ([visible, statType]: [boolean, StatTypes?]) => {
        setShowStatDetails(visible);
        if (statType !== undefined) {
          setStatType(statType);
        }
      };

      manageStatDetailsEmitter.on(
        CharacterSheet.OPEN_STAT_DETAILS,
        updateVisibility
      );

      return () => {
        manageStatDetailsEmitter.off(
          CharacterSheet.OPEN_STAT_DETAILS,
          updateVisibility
        );
      };
    }, [manageStatDetailsEmitter]);

    const closeStatDetails = () => {
      setStatType(null);
      manageStatDetailsEmitter.emit(CharacterSheet.OPEN_STAT_DETAILS, [false]);
    };

    return {
      showStatDetails,
      statType,
      closeStatDetails,
    };
  };
