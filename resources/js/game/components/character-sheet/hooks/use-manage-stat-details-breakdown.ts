import { useEventSystem } from 'event-system/hooks/use-event-system';

import { StatTypes } from '../enums/stat-types';
import { CharacterSheet } from '../event-types/character-sheet';
import UseManageStatDetailsBreakdownDefinition from './definitions/use-manage-stat-details-breakdown-definition';

export const useManageStatDetailsBreakdown =
  (): UseManageStatDetailsBreakdownDefinition => {
    const eventSystem = useEventSystem();

    const manageStatDetailsEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_STAT_DETAILS
    )
      ? eventSystem.getEventEmitter<{ [key: string]: [boolean, StatTypes?] }>(
          CharacterSheet.OPEN_STAT_DETAILS
        )
      : eventSystem.registerEvent<{ [key: string]: [boolean, StatTypes?] }>(
          CharacterSheet.OPEN_STAT_DETAILS
        );

    const openStatDetails = (statType: StatTypes) => {
      manageStatDetailsEmitter.emit(CharacterSheet.OPEN_STAT_DETAILS, [
        true,
        statType,
      ]);
    };

    return {
      openStatDetails,
    };
  };
