import { useEventSystem } from 'event-system/hooks/use-event-system';

import { AttackTypes } from '../enums/attack-types';
import { CharacterSheet } from '../event-types/character-sheet';
import UseManageAttackDetailsBreakdown from './definitions/use-manage-attack-details-breakdown';

export const useManageAttackDetailsBreakdown =
  (): UseManageAttackDetailsBreakdown => {
    const eventSystem = useEventSystem();

    const manageAttackDetailsEmitter = eventSystem.isEventRegistered(
      CharacterSheet.OPEN_ATTACK_DETAILS
    )
      ? eventSystem.getEventEmitter<{ [key: string]: [boolean, AttackTypes?] }>(
          CharacterSheet.OPEN_ATTACK_DETAILS
        )
      : eventSystem.registerEvent<{ [key: string]: [boolean, AttackTypes?] }>(
          CharacterSheet.OPEN_ATTACK_DETAILS
        );

    const openAttackDetails = (attackType: AttackTypes) => {
      manageAttackDetailsEmitter.emit(CharacterSheet.OPEN_INVENTORY_SECTION, [
        true,
        attackType,
      ]);
    };

    return {
      openAttackDetails,
    };
  };
