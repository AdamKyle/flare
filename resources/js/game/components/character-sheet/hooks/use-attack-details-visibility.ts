import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { AttackTypes } from '../enums/attack-types';
import { CharacterSheet } from '../event-types/character-sheet';
import UseManageAttackDetailsVisibilityDefinition from './definitions/use-manage-attack-details-visibility-definition';

export const useAttackDetailsVisibility =
  (): UseManageAttackDetailsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showAttackType, setShowAttackType] = useState<boolean>(false);
    const [attackType, setAttackType] = useState<AttackTypes | null>(null);

    const manageAttackDetailsEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: [boolean, AttackTypes?];
    }>(CharacterSheet.OPEN_ATTACK_DETAILS);

    useEffect(() => {
      const updateVisibility = ([visible, attackType]: [
        boolean,
        AttackTypes?,
      ]) => {
        setShowAttackType(visible);
        if (attackType !== undefined) {
          setAttackType(attackType);
        }
      };

      manageAttackDetailsEmitter.on(
        CharacterSheet.OPEN_INVENTORY_SECTION,
        updateVisibility
      );

      return () => {
        manageAttackDetailsEmitter.off(
          CharacterSheet.OPEN_INVENTORY_SECTION,
          updateVisibility
        );
      };
    }, [manageAttackDetailsEmitter]);

    const closeAttackDetails = () => {
      setAttackType(null);
      manageAttackDetailsEmitter.emit(CharacterSheet.OPEN_INVENTORY_SECTION, [
        false,
      ]);
    };

    return {
      closeAttackDetails,
      attackType,
      showAttackType,
    };
  };
