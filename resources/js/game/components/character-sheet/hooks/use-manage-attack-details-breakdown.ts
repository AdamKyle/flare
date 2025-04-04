import { useEventSystem } from 'event-system/hooks/use-event-system';

import { AttackTypes } from '../enums/attack-types';
import { CharacterSheet } from '../event-types/character-sheet';
import UseManageAttackDetailsBreakdown from './definitions/use-manage-attack-details-breakdown';

export const useManageAttackDetailsBreakdown =
  (): UseManageAttackDetailsBreakdown => {
    const eventSystem = useEventSystem();

    const manageAttackDetailsEmitter = eventSystem.fetchOrCreateEventEmitter(
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
