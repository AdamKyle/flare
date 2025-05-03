import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { StatTypes } from '../enums/stat-types';
import { CharacterSheet } from '../event-types/character-sheet';
import UseStatDefinitionVisibilityDefinition from './definitions/use-stat-definition-visibility-definition';

export const useStatDetailsVisibility =
  (): UseStatDefinitionVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showStatDetails, setShowStatDetails] = useState<boolean>(false);

    const [statType, setStatType] = useState<StatTypes | null>(null);

    const manageStatDetailsEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: [boolean, StatTypes?];
    }>(CharacterSheet.OPEN_STAT_DETAILS);

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
